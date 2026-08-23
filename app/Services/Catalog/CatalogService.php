<?php

namespace App\Services\Catalog;

use App\Http\Resources\BannerResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\DynamicPageResource;
use App\Http\Resources\HomeSectionResource;
use App\Http\Resources\ProductResource;
use App\Models\Banner;
use App\Models\Category;
use App\Models\DisplaySection;
use App\Models\DynamicPage;
use App\Models\HomeSection;
use App\Models\Product;
use App\Support\Constants;
use App\Support\StoreSettings;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CatalogService
{
    public function productRelations(): array
    {
        return ['images', 'primaryImage', 'category'];
    }

    public function storefront(): array
    {
        $relations = $this->productRelations();

        $categories = $this->rootTree();

        $products = Product::query()
            ->active()
            ->with($relations)
            ->orderBy('sort_order')
            ->orderByDesc('review_count')
            ->get();

        $offers = $products
            ->filter(fn (Product $product) => $product->has_discount)
            ->take(8)
            ->values();

        $sections = HomeSection::query()
            ->active()
            ->with(['products' => fn ($q) => $q->active()->with($relations)])
            ->get();

        $banners = Banner::query()->currentlyVisible()->get();

        $pages = DynamicPage::query()
            ->active()
            ->with(['products' => fn ($q) => $q->active()->with($relations)])
            ->get();

        return [
            'banners' => BannerResource::collection($banners)->resolve(),
            'categories' => CategoryResource::collection($categories)->resolve(),
            'offers' => ProductResource::collection($offers)->resolve(),
            'sections' => HomeSectionResource::collection($sections)->resolve(),
            'display_sections' => $this->displaySectionsFromTree($categories),
            'dynamic_pages' => DynamicPageResource::collection($pages)->resolve(),
            'products' => ProductResource::collection($products)->resolve(),
            'store' => StoreSettings::payload(),
        ];
    }

    public function findDynamicPage(string $id): ?DynamicPage
    {
        return DynamicPage::query()
            ->active()
            ->with(['products' => fn ($q) => $q->active()->with($this->productRelations())])
            ->where('id', $id)
            ->first();
    }

    public function paginateProducts(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? Constants::DEFAULT_PAGE_SIZE);
        $perPage = max(1, min($perPage, 50));

        return Product::query()
            ->active()
            ->with($this->productRelations())
            ->forCategory($filters['category_id'] ?? null)
            ->search($filters['q'] ?? $filters['search'] ?? null)
            ->when(($filters['offers'] ?? null) === '1' || ($filters['offers'] ?? null) === 'true', function ($query) {
                $query->whereNotNull('discount_price')
                    ->whereColumn('discount_price', '<', 'price');
            })
            ->when(($filters['featured'] ?? null) === '1', fn ($query) => $query->featured())
            ->orderBy('sort_order')
            ->orderByDesc('review_count')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findProduct(string $id): ?Product
    {
        $relations = $this->productRelations();

        return Product::query()
            ->active()
            ->with([
                ...$relations,
                'complementaryProducts' => fn ($q) => $q->active()->with($relations),
            ])
            ->where(function ($query) use ($id) {
                $query->where('id', $id)->orWhere('slug', $id)->orWhere('sku', $id);
            })
            ->first();
    }

    public function categories(): array
    {
        return CategoryResource::collection($this->rootTree())->resolve();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Category>
     */
    private function rootTree()
    {
        $categories = Category::query()
            ->active()
            ->roots()
            ->with([
                'children' => fn ($q) => $q->active()->orderBy('sort_order')->orderBy('name')->with([
                    'children' => fn ($c) => $c->active()->orderBy('sort_order')->orderBy('name'),
                ]),
            ])
            ->get();

        $this->attachTreeProductCounts($categories);
        foreach ($categories as $root) {
            $this->attachTreeProductCounts($root->children);
            foreach ($root->children as $branch) {
                $this->attachTreeProductCounts($branch->children);
            }
        }

        return $categories;
    }

    /**
     * تبويب الأقسام في التطبيق = الأقسام الرئيسية، والبطاقات = الأقسام الفرعية، والشرائح = التصنيفات.
     *
     * @param  \Illuminate\Support\Collection<int, Category>|\Illuminate\Database\Eloquent\Collection<int, Category>  $roots
     * @return list<array<string, mixed>>
     */
    private function displaySectionsFromTree($roots): array
    {
        $emojiBySlug = DisplaySection::query()->pluck('emoji', 'slug');

        return $roots->map(function (Category $root) use ($emojiBySlug) {
            return [
                'id' => (string) $root->id,
                'name' => $root->name,
                'slug' => $root->slug,
                'emoji' => $emojiBySlug[$root->slug] ?? '',
                'categories' => CategoryResource::collection($root->children)->resolve(),
            ];
        })->values()->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Category>|\Illuminate\Database\Eloquent\Collection<int, Category>  $categories
     */
    private function attachTreeProductCounts($categories): void
    {
        if ($categories->isEmpty()) {
            return;
        }

        $rootIds = $categories->pluck('id')->map(fn ($id) => (int) $id)->values();
        $allIds = $this->descendantCategoryIds($rootIds);
        $counts = Product::query()
            ->active()
            ->whereIn('category_id', $allIds)
            ->selectRaw('category_id, COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id');

        $children = Category::query()
            ->whereIn('id', $allIds)
            ->get(['id', 'parent_id']);
        $childrenByParent = $children->groupBy('parent_id');

        $sumOf = function ($id) use (&$sumOf, $counts, $childrenByParent): int {
            $total = (int) ($counts[$id] ?? 0);
            foreach ($childrenByParent->get($id, collect()) as $child) {
                $total += $sumOf($child->id);
            }

            return $total;
        };

        foreach ($categories as $category) {
            $category->setAttribute('products_count', $sumOf($category->id));
            if ($category->relationLoaded('children')) {
                foreach ($category->children as $child) {
                    $child->setAttribute('products_count', $sumOf($child->id));
                }
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>|array<int, int>  $rootIds
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function descendantCategoryIds($rootIds)
    {
        $all = collect($rootIds)->map(fn ($id) => (int) $id)->unique()->values();
        $frontier = $all;
        while ($frontier->isNotEmpty()) {
            $children = Category::query()->whereIn('parent_id', $frontier)->pluck('id');
            $frontier = $children->diff($all)->values();
            $all = $all->merge($frontier)->unique()->values();
        }

        return $all;
    }
}
