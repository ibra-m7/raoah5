<?php

namespace App\Services\Catalog;

use App\Enums\PromoType;
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
use App\Models\ProductBundle;
use App\Models\User;
use App\Support\Constants;
use App\Support\StoreSettings;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class CatalogService
{
    public function productRelations(): array
    {
        return [
            'images',
            'primaryImage',
            'category',
            'giftProducts' => fn ($query) => $query
                ->active()
                ->where('stock', '>', 0)
                ->with(['primaryImage']),
        ];
    }

    public function storefront(?User $user = null): array
    {
        $relations = $this->productRelations();

        $categories = $this->rootTree();

        $products = Product::query()
            ->active()
            ->sellable()
            ->with($relations)
            ->orderBy('sort_order')
            ->orderByDesc('review_count')
            ->get();

        $promoProducts = $products
            ->filter(fn (Product $product) => $product->has_discount)
            ->values();

        $discounts = $promoProducts
            ->filter(fn (Product $product) => $product->promo_type !== PromoType::Offer)
            ->take(8)
            ->values();

        $offers = $promoProducts
            ->filter(fn (Product $product) => $product->promo_type === PromoType::Offer)
            ->take(8)
            ->values();

        $sections = $this->optionalCollect(
            fn () => HomeSection::query()
                ->active()
                ->with([
                    'products' => fn ($q) => $q->active()->with($relations),
                    'bundles' => fn ($q) => $q->active()->with($this->bundleRelations()),
                ])
                ->get()
        );

        $banners = $this->optionalCollect(
            fn () => Banner::query()->currentlyVisible()->get()
        );

        $pages = $this->optionalCollect(
            fn () => DynamicPage::query()
                ->active()
                ->with(['products' => fn ($q) => $q->active()->with($relations)])
                ->get()
        );

        return [
            'banners' => BannerResource::collection($banners)->resolve(),
            'categories' => CategoryResource::collection($categories)->resolve(),
            'discounts' => ProductResource::collection($discounts)->resolve(),
            'offers' => ProductResource::collection($offers)->resolve(),
            'sections' => HomeSectionResource::collection($sections)->resolve(),
            'display_sections' => $this->displaySectionsFromTree($categories),
            'dynamic_pages' => DynamicPageResource::collection($pages)->resolve(),
            'products' => ProductResource::collection($products)->resolve(),
            'suggested' => $this->suggestedPayload($user),
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

    public function findBundle(string $id): ?ProductBundle
    {
        return ProductBundle::query()
            ->active()
            ->with($this->bundleRelations())
            ->where(function ($query) use ($id) {
                $query->where('id', $id)->orWhere('slug', $id);
            })
            ->first();
    }

    /**
     * @return array<int, string|\Closure>
     */
    public function bundleRelations(): array
    {
        $relations = $this->productRelations();

        return [
            'items' => fn ($q) => $q->orderBy('sort_order')->with([
                'product' => fn ($productQuery) => $productQuery->active()->sellable()->with($relations),
            ]),
        ];
    }

    public function paginateProducts(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? Constants::DEFAULT_PAGE_SIZE);
        $perPage = max(1, min($perPage, 50));

        return Product::query()
            ->active()
            ->sellable()
            ->with($this->productRelations())
            ->forCategory($filters['category_id'] ?? null)
            ->search($filters['q'] ?? $filters['search'] ?? null)
            ->when(($filters['offers'] ?? null) === '1' || ($filters['offers'] ?? null) === 'true', function ($query) {
                $query->whereNotNull('discount_price')
                    ->whereColumn('discount_price', '<', 'price');
            })
            ->when(
                in_array((string) ($filters['promo_type'] ?? ''), ['discount', 'offer'], true),
                function ($query) use ($filters) {
                    $type = PromoType::from((string) $filters['promo_type']);
                    $query->whereNotNull('discount_price')
                        ->whereColumn('discount_price', '<', 'price')
                        ->where('promo_type', $type);
                },
            )
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
            ->sellable()
            ->with([
                ...$relations,
                'productRelations',
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
            ->sellable()
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
     * @template TValue
     * @param  callable(): Collection<int, TValue>  $load
     * @return Collection<int, TValue>
     */
    private function optionalCollect(callable $load): Collection
    {
        try {
            return $load();
        } catch (QueryException $e) {
            if (! $this->isMissingTable($e)) {
                throw $e;
            }

            Log::warning('catalog.missing_table', [
                'error' => mb_substr($e->getMessage(), 0, 180),
            ]);

            return collect();
        }
    }

    private function isMissingTable(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');

        return $sqlState === '42P01'
            || str_contains($e->getMessage(), 'does not exist')
            || str_contains($e->getMessage(), "doesn't exist");
    }

    /**
     * @return array<int, mixed>
     */
    private function suggestedPayload(?User $user): array
    {
        try {
            return ProductResource::collection(
                app(ProductRecommendationService::class)->forYou($user)
            )->resolve();
        } catch (Throwable $e) {
            Log::warning('reco.storefront.failed', [
                'error' => mb_substr($e->getMessage(), 0, 180),
            ]);

            return [];
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
