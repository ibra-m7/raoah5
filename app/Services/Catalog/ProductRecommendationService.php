<?php

namespace App\Services\Catalog;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\User;
use App\Services\Ai\RecommendationTrainer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProductRecommendationService
{
    public function __construct(
        private readonly ProductRecommendationEngine $engine,
        private readonly RecommendationTrainer $trainer,
    ) {}

    /**
     * @return array{bought_together: array<int, mixed>, similar: array<int, mixed>, suggested: array<int, mixed>}
     */
    public function forProduct(Product $product): array
    {
        $product->loadMissing(['category', 'productRelations']);

        $bought = $this->applyCachedRank(
            'bought_together',
            ['product_id' => $product->id],
            $this->engine->boughtTogether($product, 12)
        );
        $similar = $this->applyCachedRank(
            'similar',
            ['product_id' => $product->id],
            $this->engine->similar($product, 12)
        );

        return [
            'bought_together' => $this->resources($bought->take(8)),
            'similar' => $this->resources($similar->take(8)),
            'suggested' => $this->resources($this->forYou(null, [(int) $product->id], 8)),
        ];
    }

    /**
     * @param  list<int>  $productIds
     * @return array{complete_cart: array<int, mixed>, suggested: array<int, mixed>}
     */
    public function forCart(array $productIds, ?User $user = null): array
    {
        $complete = $this->applyCachedRank(
            'complete_cart',
            ['product_ids' => $productIds],
            $this->engine->completeCart($productIds, 12)
        );

        return [
            'complete_cart' => $this->resources($complete->take(8)),
            'suggested' => $this->resources($this->forYou($user, $productIds, 8)),
        ];
    }

    /**
     * @param  list<int>  $excludeIds
     * @return Collection<int, Product>
     */
    public function forYou(?User $user, array $excludeIds = [], int $limit = 10): Collection
    {
        $cacheKey = 'reco:foryou:'.($user?->id ?? 'guest').':'.md5(implode(',', $excludeIds));

        $ids = Cache::remember($cacheKey, now()->addMinutes(20), function () use ($user, $excludeIds, $limit) {
            return $this->engine->forYou($user, $excludeIds, $limit)->pluck('id')->all();
        });

        $products = Product::query()
            ->active()
            ->with($this->engine->relations())
            ->whereIn('id', $ids)
            ->get();

        return $this->engine->applyOrder($products, array_map('intval', $ids))->take($limit)->values();
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return Collection<int, Product>
     */
    private function applyCachedRank(string $mechanism, array $anchor, Collection $products): Collection
    {
        $ranked = $this->trainer->cachedRank(
            $mechanism,
            $this->trainer->contextKey($anchor, $products),
            $products
        );

        if ($ranked === null || $ranked === []) {
            $ranked = $this->trainer->cachedRank(
                $mechanism,
                $this->trainer->contextKey($anchor, collect()),
                $products
            );
        }

        return $ranked ? $this->engine->applyOrder($products, $ranked) : $products;
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<int, mixed>
     */
    private function resources(Collection $products): array
    {
        return ProductResource::collection($products->values())->resolve();
    }
}
