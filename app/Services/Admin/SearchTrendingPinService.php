<?php

namespace App\Services\Admin;

use App\Models\SearchTrendingPin;
use App\Support\Constants;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class SearchTrendingPinService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return SearchTrendingPin::query()
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where('phrase', 'like', '%'.$search.'%');
            })
            ->when(($filters['status'] ?? '') === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? '') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();
    }

    public function create(array $data): SearchTrendingPin
    {
        return SearchTrendingPin::query()->create($this->payload($data));
    }

    public function update(SearchTrendingPin $pin, array $data): SearchTrendingPin
    {
        $pin->update($this->payload($data));

        return $pin->fresh();
    }

    public function delete(SearchTrendingPin $pin): void
    {
        $pin->delete();
    }

    /**
     * @return list<string>
     */
    public static function activePhrases(): array
    {
        return SearchTrendingPin::query()
            ->active()
            ->pluck('phrase')
            ->map(fn ($phrase) => trim((string) $phrase))
            ->filter(fn ($phrase) => $phrase !== '')
            ->values()
            ->all();
    }

    /**
     * @return array{phrase: string, sort_order: int, is_active: bool}
     */
    private function payload(array $data): array
    {
        $phrase = trim((string) ($data['phrase'] ?? ''));
        if ($phrase === '') {
            throw ValidationException::withMessages([
                'phrase' => 'نص الكلمة مطلوب.',
            ]);
        }

        return [
            'phrase' => $phrase,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}
