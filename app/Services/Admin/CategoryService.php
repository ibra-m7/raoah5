<?php

namespace App\Services\Admin;

use App\Enums\BannerLinkType;
use App\Models\Banner;
use App\Models\Category;
use App\Models\DisplaySection;
use App\Support\Constants;
use App\Support\Media;
use App\Support\Slug;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public const TAB_MAINS = 'mains';

    public const TAB_BRANCHES = 'branches';

    public const TAB_CLASSES = 'classes';

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Category::query()
            ->with('parent')
            ->withCount(['products', 'children'])
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->when(($filters['parent_id'] ?? '') === 'root', fn ($query) => $query->whereNull('parent_id'))
            ->when(is_numeric($filters['parent_id'] ?? null), fn ($query) => $query->where('parent_id', $filters['parent_id']))
            ->when(($filters['status'] ?? '') === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? '') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();
    }

    public function tree(): Collection
    {
        $all = Category::query()
            ->with('displaySections')
            ->withCount(['products', 'children'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->nest($all);
    }

    public function parentOptions(?int $exceptId = null): Collection
    {
        return $this->indentedOptions($exceptId);
    }

    public function indentedOptions(?int $exceptId = null): Collection
    {
        $flat = collect();
        $this->flattenTree($this->tree(), $flat, '', $exceptId);

        return $flat;
    }

    /**
     * @return array{mains: Collection, branches: Collection, classes: Collection}
     */
    public function groupedByLevel(): array
    {
        $flat = $this->indentedOptions();

        return [
            self::TAB_MAINS => $flat->where('depth', 0)->values(),
            self::TAB_BRANCHES => $flat->where('depth', 1)->values(),
            self::TAB_CLASSES => $flat->filter(fn ($category) => (int) $category->depth >= 2)->values(),
        ];
    }

    public function itemsForTab(string $tab, ?Category $scope): Collection
    {
        $groups = $this->groupedByLevel();

        return match ($tab) {
            self::TAB_BRANCHES => $this->filterByScope($groups[self::TAB_BRANCHES], $scope),
            self::TAB_CLASSES => $this->filterByScope($groups[self::TAB_CLASSES], $scope),
            default => $groups[self::TAB_MAINS],
        };
    }

    public function scopedItems(Collection $items, ?Category $scope): Collection
    {
        return $this->filterByScope($items, $scope);
    }

    public function filterOptions(string $tab): Collection
    {
        $flat = $this->indentedOptions();

        return match ($tab) {
            self::TAB_BRANCHES => $flat->where('depth', 0)->values(),
            self::TAB_CLASSES => $flat->where('depth', 1)->values(),
            default => collect(),
        };
    }

    public function ancestorChain(?Category $scope): Collection
    {
        $chain = collect();
        $current = $scope;
        $guard = 0;
        while ($current && $guard++ < 12) {
            $chain->prepend($current);
            $current = $current->parent_id
                ? Category::query()->find($current->parent_id)
                : null;
        }

        return $chain;
    }

    public function productCategoryOptions(?int $currentId = null): Collection
    {
        return $this->indentedOptions()
            ->filter(function ($category) use ($currentId) {
                if ($currentId && (int) $category->id === $currentId) {
                    return true;
                }

                $depth = (int) $category->depth;
                if ($depth >= 2) {
                    return true;
                }

                return $depth === 1 && (int) $category->children_count === 0;
            })
            ->values();
    }

    public function tabFor(?int $parentId): string
    {
        $depth = $this->depthFor($parentId);

        return match (true) {
            $depth <= 0 => self::TAB_MAINS,
            $depth === 1 => self::TAB_BRANCHES,
            default => self::TAB_CLASSES,
        };
    }

    public function depthOf(Category $category): int
    {
        return $this->depthFor($category->parent_id);
    }

    public function displaySectionOptions(): Collection
    {
        return DisplaySection::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'emoji', 'is_active']);
    }

    public static function levelLabel(int $depth): string
    {
        return match (true) {
            $depth <= 0 => 'قسم رئيسي',
            $depth === 1 => 'قسم فرعي',
            default => 'تصنيف',
        };
    }

    public function depthFor(?int $parentId): int
    {
        $depth = 0;
        $current = $parentId;
        $guard = 0;
        while ($current && $guard++ < 12) {
            $depth++;
            $current = Category::query()->whereKey($current)->value('parent_id');
        }

        return $depth;
    }

    public function create(array $data): Category
    {
        $this->pullSectionIds($data);
        $data['slug'] = Slug::unique($data['name'], 'categories');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['parent_id'] = $data['parent_id'] ?: null;
        $data['icon_url'] = Media::store($data['icon'] ?? null, 'categories/icons');
        $data['image_url'] = Media::store($data['image'] ?? null, 'categories/images');
        unset($data['icon'], $data['image']);

        $category = Category::query()->create($data);
        $this->syncRootDisplaySection($category);

        return $category;
    }

    public function update(Category $category, array $data): Category
    {
        $parentId = ! empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        if ($parentId === $category->id || ($parentId && $this->isDescendantOf($parentId, $category->id))) {
            throw ValidationException::withMessages([
                'parent_id' => 'لا يمكن جعل القسم أباً لنفسه أو لأحد فروعه.',
            ]);
        }

        $this->pullSectionIds($data);
        $data['slug'] = Slug::unique($data['name'], 'categories', 'slug', $category->id);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['parent_id'] = $parentId;
        $data['icon_url'] = Media::store($data['icon'] ?? null, 'categories/icons', $category->icon_url);
        $data['image_url'] = Media::store($data['image'] ?? null, 'categories/images', $category->image_url);
        unset($data['icon'], $data['image']);

        $category->update($data);
        $this->syncRootDisplaySection($category->fresh());

        return $category;
    }

    public function delete(Category $category): ?string
    {
        if (! auth()->user()?->isAdmin()) {
            if ($category->products()->exists()) {
                throw ValidationException::withMessages([
                    'category' => 'لا يمكن حذف القسم لأن هناك منتجات مرتبطة به.',
                ]);
            }

            if ($category->children()->exists()) {
                throw ValidationException::withMessages([
                    'category' => 'لا يمكن حذف القسم قبل نقل أو حذف الأقسام الفرعية.',
                ]);
            }
        }

        return DB::transaction(function () use ($category) {
            $category->load('parent');
            $parentId = $category->parent_id;
            $childIds = $category->children()->pluck('id');
            $hasProducts = $category->products()->exists();
            $root = $this->rootOf($category);

            $category->children()->update(['parent_id' => $parentId]);

            $destinationId = $parentId
                ?? $childIds->first()
                ?? Category::query()->where('id', '!=', $category->id)->orderBy('sort_order')->orderBy('id')->value('id');

            if ($hasProducts) {
                if (! $destinationId) {
                    throw ValidationException::withMessages([
                        'category' => 'لا يمكن حذف آخر قسم وفيه منتجات. أنشئ قسماً آخر أولاً لنقل المنتجات إليه.',
                    ]);
                }

                $category->products()->update(['category_id' => $destinationId]);
            }

            Banner::query()
                ->where('link_type', BannerLinkType::Category)
                ->where('link_id', $category->id)
                ->update([
                    'link_type' => BannerLinkType::None,
                    'link_id' => null,
                ]);

            $destinationName = $hasProducts && $destinationId
                ? Category::query()->where('id', $destinationId)->value('name')
                : null;

            Media::delete($category->icon_url);
            Media::delete($category->image_url);
            $category->displaySections()->detach();
            $category->delete();

            if ($root && (int) $root->id !== (int) $category->id) {
                $this->syncRootDisplaySection($root->fresh());
            }

            return $destinationName;
        });
    }

    /**
     * @param  Collection<int, Category>  $all
     * @return Collection<int, Category>
     */
    private function nest(Collection $all, ?int $parentId = null): Collection
    {
        return $all
            ->where('parent_id', $parentId)
            ->values()
            ->map(function (Category $category) use ($all) {
                $category->setRelation('children', $this->nest($all, $category->id));

                return $category;
            });
    }

    /**
     * @param  Collection<int, Category>  $nodes
     * @param  Collection<int, Category>  $flat
     */
    private function flattenTree(Collection $nodes, Collection $flat, string $prefix, ?int $exceptId, int $depth = 0): void
    {
        foreach ($nodes as $node) {
            if ($exceptId && (int) $node->id === $exceptId) {
                continue;
            }

            $label = $prefix === '' ? $node->name : $prefix.' ← '.$node->name;
            $node->setAttribute('path_label', $label);
            $node->setAttribute('depth', $depth);
            $flat->push($node);
            $this->flattenTree($node->children, $flat, $label, $exceptId, $depth + 1);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<int>
     */
    private function pullSectionIds(array &$data): array
    {
        $ids = collect($data['display_section_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        unset($data['display_section_ids'], $data['level']);

        return $ids;
    }

    private function filterByScope(Collection $items, ?Category $scope): Collection
    {
        if (! $scope) {
            return $items;
        }

        return $items
            ->filter(fn ($item) => $this->isDescendantOf((int) $item->id, (int) $scope->id))
            ->values();
    }

    private function syncRootDisplaySection(?Category $category): void
    {
        $root = $this->rootOf($category);
        if (! $root) {
            return;
        }

        $section = DisplaySection::query()->updateOrCreate(
            ['slug' => $root->slug],
            [
                'name' => $root->name,
                'sort_order' => $root->sort_order,
                'is_active' => $root->is_active,
            ],
        );

        $sync = [];
        foreach (Category::query()->where('parent_id', $root->id)->orderBy('sort_order')->orderBy('name')->pluck('id') as $index => $id) {
            $sync[$id] = ['sort_order' => $index];
        }
        $section->categories()->sync($sync);
    }

    private function rootOf(?Category $category): ?Category
    {
        $current = $category;
        $guard = 0;
        while ($current && $current->parent_id && $guard++ < 12) {
            $current = $current->parent ?: Category::query()->find($current->parent_id);
        }

        return $current;
    }

    public function isDescendantOf(int $maybeChildId, int $ancestorId): bool
    {
        if ($maybeChildId === $ancestorId) {
            return true;
        }

        $current = Category::query()->whereKey($maybeChildId)->value('parent_id');
        $guard = 0;
        while ($current && $guard++ < 20) {
            if ((int) $current === $ancestorId) {
                return true;
            }
            $current = Category::query()->whereKey($current)->value('parent_id');
        }

        return false;
    }
}
