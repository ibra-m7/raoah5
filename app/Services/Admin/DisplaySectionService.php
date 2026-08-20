<?php

namespace App\Services\Admin;

use App\Models\Category;
use App\Models\DisplaySection;
use App\Support\Constants;
use App\Support\Slug;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DisplaySectionService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return DisplaySection::query()
            ->withCount('categories')
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('sort_order')
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();
    }

    public function categoryOptions(): Collection
    {
        return Category::query()
            ->with('parent')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id', 'image_url']);
    }

    public function create(array $data): DisplaySection
    {
        $section = DisplaySection::query()->create($this->payload($data));
        $this->syncCategories($section, $data['category_ids'] ?? []);

        return $section;
    }

    public function update(DisplaySection $section, array $data): DisplaySection
    {
        $section->update($this->payload($data, $section));
        $this->syncCategories($section, $data['category_ids'] ?? []);

        return $section;
    }

    public function delete(DisplaySection $section): void
    {
        $section->categories()->detach();
        $section->delete();
    }

    private function payload(array $data, ?DisplaySection $section = null): array
    {
        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = Slug::unique($data['name'], 'display_sections', 'slug', $section?->id);
        }

        return [
            'name' => $data['name'],
            'slug' => $slug,
            'emoji' => $data['emoji'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }

    private function syncCategories(DisplaySection $section, array $ids): void
    {
        $sync = [];
        foreach (array_values(array_filter($ids)) as $i => $id) {
            $sync[(int) $id] = ['sort_order' => $i];
        }
        $section->categories()->sync($sync);
    }
}
