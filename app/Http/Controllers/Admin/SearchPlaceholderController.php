<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SearchPlaceholderRequest;
use App\Models\SearchPlaceholder;
use App\Models\SearchSmartSuggestion;
use App\Models\SearchTrendingPin;
use App\Models\User;
use App\Services\Admin\SearchDiscoveryService;
use App\Services\Admin\SearchLogService;
use App\Services\Admin\SearchPlaceholderService;
use App\Services\Admin\SearchSmartSuggestionService;
use App\Services\Admin\SearchTrendingPinService;
use App\Support\AppStrings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchPlaceholderController extends Controller
{
    public function __construct(
        private readonly SearchPlaceholderService $placeholders,
        private readonly SearchLogService $logs,
        private readonly SearchSmartSuggestionService $smartSuggestions,
        private readonly SearchTrendingPinService $trendingPins,
        private readonly SearchDiscoveryService $discovery,
    ) {}

    public function index(Request $request): View
    {
        $tab = $request->string('tab')->toString();
        if (! in_array($tab, ['phrases', 'smart', 'trending', 'customers', 'queries'], true)) {
            $tab = 'phrases';
        }

        $filters = $request->only(['q', 'status', 'match']);
        $customers = null;
        $logsByUser = collect();
        $users = collect();
        $guestLogs = collect();
        $guestLogsJson = '[]';
        $guestCount = 0;
        $customerRows = collect();
        $queries = null;
        $productsById = collect();
        $smartItems = null;
        $trendingPins = null;
        $autoTrending = collect();

        if ($tab === 'customers') {
            $customers = $this->logs->paginateCustomers($filters);
            $userIds = $customers->getCollection()->pluck('user_id')->filter()->map(fn ($id) => (int) $id)->all();
            $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');
            $logsByUser = $this->logs->recentLogsForUsers($userIds);
            $guestLogs = $this->logs->guestLogs();
            $guestCount = $this->logs->guestSearchCount();
            $guestLogsJson = json_encode(
                $this->logs->formatRows($guestLogs),
                JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT,
            );
            $customerRows = $customers->getCollection()->map(function ($row) use ($users, $logsByUser) {
                $user = $users->get($row->user_id);
                $name = $this->logs->customerDisplayName($user, (int) $row->user_id);
                $logs = $logsByUser->get($row->user_id) ?? collect();

                return (object) [
                    'user_id' => $row->user_id,
                    'name' => $name,
                    'phone' => $user?->phone,
                    'searches_count' => $row->searches_count,
                    'last_searched_at' => $row->last_searched_at,
                    'logs_json' => json_encode(
                        $this->logs->formatRows($logs),
                        JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT,
                    ),
                ];
            });
        }

        if ($tab === 'queries') {
            if (! in_array((string) ($filters['match'] ?? ''), ['all', 'found', 'missing'], true)) {
                $filters['match'] = 'all';
            }
            $queries = $this->logs->paginateQueries($filters);
            $productsById = $this->logs->productsForQueryRows($queries->getCollection());
        }

        if ($tab === 'smart') {
            $smartItems = $this->smartSuggestions->paginate($filters);
        }

        if ($tab === 'trending') {
            $trendingPins = $this->trendingPins->paginate($filters);
            $autoTrending = $this->discovery->topQueriesFromLogs(12);
        }

        $openPhraseModal = $request->boolean('open_phrase')
            || ($request->session()->hasOldInput() && old('form') === 'phrase');
        $openSmartModal = $request->boolean('open_smart')
            || ($request->session()->hasOldInput() && old('form') === 'smart');
        $openTrendingModal = $request->boolean('open_trending')
            || ($request->session()->hasOldInput() && old('form') === 'trending');

        return view('admin.search-placeholders.index', [
            'title' => AppStrings::NAV_SEARCH_PAGE,
            'tab' => $tab,
            'placeholders' => $this->placeholders->paginate($tab === 'phrases' ? $filters : []),
            'filters' => $filters,
            'customers' => $customers,
            'customerRows' => $customerRows,
            'users' => $users,
            'logsByUser' => $logsByUser,
            'guestLogs' => $guestLogs,
            'guestLogsJson' => $guestLogsJson,
            'guestCount' => $guestCount,
            'queries' => $queries,
            'productsById' => $productsById,
            'smartItems' => $smartItems,
            'trendingPins' => $trendingPins,
            'autoTrending' => $autoTrending,
            'openPhraseModal' => $openPhraseModal,
            'openSmartModal' => $openSmartModal,
            'openTrendingModal' => $openTrendingModal,
            'modalPlaceholder' => $this->modalPlaceholder(),
            'modalSmart' => $this->modalSmart(),
            'modalTrending' => $this->modalTrending(),
        ]);
    }

    public function store(SearchPlaceholderRequest $request): RedirectResponse
    {
        $this->placeholders->create($request->validated());

        return redirect()
            ->route('admin.search-placeholders.index', ['tab' => 'phrases'])
            ->with('success', AppStrings::SEARCH_PLACEHOLDER_CREATED);
    }

    public function update(
        SearchPlaceholderRequest $request,
        SearchPlaceholder $search_placeholder,
    ): RedirectResponse {
        $this->placeholders->update($search_placeholder, $request->validated());

        return redirect()
            ->route('admin.search-placeholders.index', ['tab' => 'phrases'])
            ->with('success', AppStrings::SEARCH_PLACEHOLDER_UPDATED);
    }

    public function destroy(SearchPlaceholder $search_placeholder): RedirectResponse
    {
        $this->placeholders->delete($search_placeholder);

        return redirect()
            ->route('admin.search-placeholders.index', ['tab' => 'phrases'])
            ->with('success', AppStrings::SEARCH_PLACEHOLDER_DELETED);
    }

    private function modalPlaceholder(): SearchPlaceholder
    {
        if (old('form') === 'phrase' && old('editing_id')) {
            $existing = SearchPlaceholder::query()->find(old('editing_id'));
            if ($existing) {
                return $existing;
            }
        }

        return new SearchPlaceholder([
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function modalSmart(): SearchSmartSuggestion
    {
        if (old('form') === 'smart' && old('editing_id')) {
            $existing = SearchSmartSuggestion::query()->find(old('editing_id'));
            if ($existing) {
                return $existing;
            }
        }

        return new SearchSmartSuggestion([
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function modalTrending(): SearchTrendingPin
    {
        if (old('form') === 'trending' && old('editing_id')) {
            $existing = SearchTrendingPin::query()->find(old('editing_id'));
            if ($existing) {
                return $existing;
            }
        }

        return new SearchTrendingPin([
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }
}
