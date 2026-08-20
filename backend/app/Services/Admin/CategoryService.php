<?php

namespace App\Services\Admin;

use App\Enums\BannerLinkType;
use App\Models\Banner;
use App\Models\Category;
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

    public function parentOptions(?int $exceptId = null): Collection
    {
        return Category::query()
            ->when($exceptId, function ($query) use ($exceptId) {
                $query->where('id', '!=', $exceptId)
                    ->where(function ($nested) use ($exceptId) {
                        $nested->whereNull('parent_id')
                            ->orWhere('parent_id', '!=', $exceptId);
                    });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);
    }

    public function create(array $data): Category
    {
        $data['slug'] = Slug::unique($data['name'], 'categories');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['parent_id'] = $data['parent_id'] ?: null;
        $data['icon_url'] = Media::store($data['icon'] ?? null, 'categories/icons');
        $data['image_url'] = Media::store($data['image'] ?? null, 'categories/images');
        unset($data['icon'], $data['image']);

        return Category::query()->create($data);
    }

    public function update(Category $category, array $data): Category
    {
        if (! empty($data['parent_id']) && (int) $data['parent_id'] === $category->id) {
            throw ValidationException::withMessages([
                'parent_id' => 'لا يمكن جعل القسم أباً لنفسه.',
            ]);
        }

        $data['slug'] = Slug::unique($data['name'], 'categories', 'slug', $category->id);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['parent_id'] = $data['parent_id'] ?: null;
        $data['icon_url'] = Media::store($data['icon'] ?? null, 'categories/icons', $category->icon_url);
        $data['image_url'] = Media::store($data['image'] ?? null, 'categories/images', $category->image_url);
        unset($data['icon'], $data['image']);

        $category->update($data);

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
            $category->delete();

            return $destinationName;
        });
    }
}
