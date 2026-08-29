<?php

namespace App\Services\Admin;

use App\Models\SearchSmartSuggestion;
use App\Support\Constants;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class SearchSmartSuggestionService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return SearchSmartSuggestion::query()
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

    public function create(array $data): SearchSmartSuggestion
    {
        return SearchSmartSuggestion::query()->create($this->payload($data));
    }

    public function update(SearchSmartSuggestion $suggestion, array $data): SearchSmartSuggestion
    {
        $suggestion->update($this->payload($data));

        return $suggestion->fresh();
    }

    public function delete(SearchSmartSuggestion $suggestion): void
    {
        $suggestion->delete();
    }

    /**
     * @return list<string>
     */
    public static function activePhrases(): array
    {
        return SearchSmartSuggestion::query()
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
                'phrase' => 'نص الاقتراح مطلوب.',
            ]);
        }

        return [
            'phrase' => $phrase,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}
