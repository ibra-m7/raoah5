<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\SearchLog;
use App\Models\User;
use App\Support\Constants;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class SearchLogService
{
    /**
     * @param  array{query?: string, matched_product_id?: int|string|null, results_count?: int|string|null, source?: string|null}  $input
     */
    public function record(array $input, ?User $user = null): ?SearchLog
    {
        $query = trim((string) ($input['query'] ?? ''));
        if (mb_strlen($query) < 1) {
            return null;
        }
        if (mb_strlen($query) > 191) {
            $query = mb_substr($query, 0, 191);
        }

        $matchedId = isset($input['matched_product_id']) && $input['matched_product_id'] !== ''
            ? (int) $input['matched_product_id']
            : null;
        $resultsCount = max(0, (int) ($input['results_count'] ?? 0));

        if ($matchedId !== null && $matchedId > 0) {
            $exists = Product::query()->whereKey($matchedId)->exists();
            if (! $exists) {
                $matchedId = null;
            }
        } else {
            $matchedId = null;
        }

        if ($matchedId === null) {
            $product = Product::query()->active()->search($query)->orderBy('id')->first();
            if ($product !== null) {
                $matchedId = (int) $product->id;
                if ($resultsCount === 0) {
                    $resultsCount = 1;
                }
            }
        }

        $user ??= Auth::guard('sanctum')->user();
        if ($user !== null && ! $user instanceof User) {
            $user = null;
        }

        return SearchLog::query()->create([
            'user_id' => $user?->id,
            'query' => $query,
            'matched_product_id' => $matchedId,
            'results_count' => $resultsCount,
            'source' => trim((string) ($input['source'] ?? 'app')) ?: 'app',
            'created_at' => now(),
        ]);
    }

    public function paginateCustomers(array $filters = []): LengthAwarePaginator
    {
        return SearchLog::query()
            ->select('user_id')
            ->selectRaw('COUNT(*) as searches_count')
            ->selectRaw('MAX(created_at) as last_searched_at')
            ->whereNotNull('user_id')
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->groupBy('user_id')
            ->orderByDesc('last_searched_at')
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();
    }

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, Collection<int, SearchLog>>
     */
    public function recentLogsForUsers(array $userIds, int $limitPerUser = 40): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        $logs = SearchLog::query()
            ->with('matchedProduct:id,name')
            ->whereIn('user_id', $userIds)
            ->latest('created_at')
            ->latest('id')
            ->get();

        return $logs
            ->groupBy('user_id')
            ->map(fn (Collection $group) => $group->take($limitPerUser));
    }

    /**
     * @return Collection<int, SearchLog>
     */
    public function guestLogs(int $limit = 80): Collection
    {
        return SearchLog::query()
            ->with('matchedProduct:id,name')
            ->whereNull('user_id')
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function paginateQueries(array $filters = []): LengthAwarePaginator
    {
        $match = (string) ($filters['match'] ?? 'all');

        return SearchLog::query()
            ->select('query')
            ->selectRaw('COUNT(*) as hits')
            ->selectRaw('MAX(matched_product_id) as matched_product_id')
            ->selectRaw('MAX(created_at) as last_searched_at')
            ->selectRaw('MAX(results_count) as results_count')
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('query', 'like', '%'.$search.'%'))
            ->when($match === 'found', function ($query) {
                $query->where(function ($nested) {
                    $nested->whereNotNull('matched_product_id')
                        ->orWhere('results_count', '>', 0);
                });
            })
            ->when($match === 'missing', function ($query) {
                $query->whereNull('matched_product_id')
                    ->where('results_count', '<=', 0);
            })
            ->groupBy('query')
            ->orderByDesc('hits')
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();
    }

    /**
     * @param  Collection<int, object{matched_product_id?: int|null}>  $rows
     * @return Collection<int, Product>
     */
    public function productsForQueryRows(Collection $rows): Collection
    {
        $ids = $rows
            ->pluck('matched_product_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return collect();
        }

        return Product::query()->whereIn('id', $ids)->get()->keyBy('id');
    }

    public function guestSearchCount(): int
    {
        return (int) SearchLog::query()->whereNull('user_id')->count();
    }

    /**
     * @param  Collection<int, SearchLog>  $logs
     * @return list<array{query: string, date: string, found: bool, product: ?string}>
     */
    public function formatRows(Collection $logs): array
    {
        return $logs
            ->map(fn (SearchLog $log) => [
                'query' => $log->query,
                'date' => $log->created_at
                    ?->timezone(config('app.timezone'))
                    ->format('Y-m-d H:i') ?? '—',
                'found' => $log->hasProductMatch(),
                'product' => $log->matchedProduct?->name,
            ])
            ->values()
            ->all();
    }

    public function customerDisplayName(?User $user, int $userId): string
    {
        $name = trim((string) ($user?->name ?? ''));
        if ($name !== '' && $name !== 'عميل') {
            return $name;
        }

        if ($user?->phone) {
            return 'عميل '.$user->phone;
        }

        return 'عميل #'.$userId;
    }
}
