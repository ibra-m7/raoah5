<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SearchSmartSuggestionRequest;
use App\Models\SearchSmartSuggestion;
use App\Services\Admin\SearchSmartSuggestionService;
use App\Support\AppStrings;
use Illuminate\Http\RedirectResponse;

class SearchSmartSuggestionController extends Controller
{
    public function __construct(private readonly SearchSmartSuggestionService $suggestions) {}

    public function store(SearchSmartSuggestionRequest $request): RedirectResponse
    {
        $this->suggestions->create($request->validated());

        return redirect()
            ->route('admin.search-placeholders.index', ['tab' => 'smart'])
            ->with('success', AppStrings::SEARCH_SMART_CREATED);
    }

    public function update(
        SearchSmartSuggestionRequest $request,
        SearchSmartSuggestion $search_smart_suggestion,
    ): RedirectResponse {
        $this->suggestions->update($search_smart_suggestion, $request->validated());

        return redirect()
            ->route('admin.search-placeholders.index', ['tab' => 'smart'])
            ->with('success', AppStrings::SEARCH_SMART_UPDATED);
    }

    public function destroy(SearchSmartSuggestion $search_smart_suggestion): RedirectResponse
    {
        $this->suggestions->delete($search_smart_suggestion);

        return redirect()
            ->route('admin.search-placeholders.index', ['tab' => 'smart'])
            ->with('success', AppStrings::SEARCH_SMART_DELETED);
    }
}
