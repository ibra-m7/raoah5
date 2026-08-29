<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SearchTrendingPinRequest;
use App\Models\SearchTrendingPin;
use App\Services\Admin\SearchTrendingPinService;
use App\Support\AppStrings;
use Illuminate\Http\RedirectResponse;

class SearchTrendingPinController extends Controller
{
    public function __construct(private readonly SearchTrendingPinService $pins) {}

    public function store(SearchTrendingPinRequest $request): RedirectResponse
    {
        $this->pins->create($request->validated());

        return redirect()
            ->route('admin.search-placeholders.index', ['tab' => 'trending'])
            ->with('success', AppStrings::SEARCH_TRENDING_PIN_CREATED);
    }

    public function update(
        SearchTrendingPinRequest $request,
        SearchTrendingPin $search_trending_pin,
    ): RedirectResponse {
        $this->pins->update($search_trending_pin, $request->validated());

        return redirect()
            ->route('admin.search-placeholders.index', ['tab' => 'trending'])
            ->with('success', AppStrings::SEARCH_TRENDING_PIN_UPDATED);
    }

    public function destroy(SearchTrendingPin $search_trending_pin): RedirectResponse
    {
        $this->pins->delete($search_trending_pin);

        return redirect()
            ->route('admin.search-placeholders.index', ['tab' => 'trending'])
            ->with('success', AppStrings::SEARCH_TRENDING_PIN_DELETED);
    }
}
