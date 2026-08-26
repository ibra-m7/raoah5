<?php

namespace App\Services\Admin;

use App\Models\SearchPlaceholder;
use App\Support\Constants;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class SearchPlaceholderService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return SearchPlaceholder::query()
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

    public function create(array $data): SearchPlaceholder
    {
        return SearchPlaceholder::query()->create($this->payload($data));
    }

    public function update(SearchPlaceholder $placeholder, array $data): SearchPlaceholder
    {
        $placeholder->update($this->payload($data));

        return $placeholder->fresh();
    }

    public function delete(SearchPlaceholder $placeholder): void
    {
        $placeholder->delete();
    }

    /**
     * @return list<string>
     */
    public static function activePhrases(): array
    {
        return SearchPlaceholder::query()
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
                'phrase' => 'نص العبارة مطلوب.',
            ]);
        }

        return [
            'phrase' => $phrase,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}
