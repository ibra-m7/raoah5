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
            $depth === 1 => 'تصنيف',
            default => 'تصنيف فرعي',
        };
    }

    public static function levelHint(int $depth): string
    {
        return match (true) {
            $depth <= 0 => 'يظهر كدائرة في الصفحة الرئيسية للتطبيق.',
            $depth === 1 => 'يظهر كبطاقة داخل تبويب الأقسام.',
            default => 'يظهر كشريحة عند فتح التصنيف، لتصفية المنتجات.',
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
        $sectionIds = $this->pullSectionIds($data);
        $data['slug'] = Slug::unique($data['name'], 'categories');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['parent_id'] = $data['parent_id'] ?: null;
        $data['icon_url'] = Media::store($data['icon'] ?? null, 'categories/icons');
        $data['image_url'] = Media::store($data['image'] ?? null, 'categories/images');
        unset($data['icon'], $data['image']);

        $category = Category::query()->create($data);
        $this->syncDisplaySections($category, $sectionIds);

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

        $sectionIds = $this->pullSectionIds($data);
        $data['slug'] = Slug::unique($data['name'], 'categories', 'slug', $category->id);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['parent_id'] = $parentId;
        $data['icon_url'] = Media::store($data['icon'] ?? null, 'categories/icons', $category->icon_url);
        $data['image_url'] = Media::store($data['image'] ?? null, 'categories/images', $category->image_url);
        unset($data['icon'], $data['image']);

        $category->update($data);
        $this->syncDisplaySections($category->fresh(), $sectionIds);

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
        unset($data['display_section_ids']);

        return $ids;
    }

    /**
     * @param  list<int>  $sectionIds
     */
    private function syncDisplaySections(Category $category, array $sectionIds): void
    {
        if ($category->parent_id === null) {
            $category->displaySections()->sync([]);

            return;
        }

        $sync = [];
        foreach (array_values($sectionIds) as $index => $id) {
            $sync[$id] = ['sort_order' => $index];
        }
        $category->displaySections()->sync($sync);
    }

    private function isDescendantOf(int $maybeChildId, int $ancestorId): bool
    {
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
