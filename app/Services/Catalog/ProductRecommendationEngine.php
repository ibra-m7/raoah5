<?php

namespace App\Services\Catalog;

use App\Enums\OrderStatus;
use App\Enums\ProductRelationType;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductRecommendationEngine
{
    /**
     * @return Collection<int, Product>
     */
    public function boughtTogether(Product $product, int $limit = 8): Collection
    {
        $scores = $this->coOccurrenceScores([$product->id]);

        foreach ($this->relationsOf($product->id) as $relation) {
            $relatedId = (int) $relation->related_product_id;
            $bonus = $relation->type === ProductRelationType::Complementary ? 40.0 : 12.0;
            if (($relation->source ?? '') === 'ai') {
                $bonus += 8.0;
            }
            $scores[$relatedId] = ($scores[$relatedId] ?? 0) + $bonus;
        }

        $picked = $this->hydrateRanked($scores, [$product->id], $limit, $product->category_id, true);
        if ($picked->count() >= $limit) {
            return $picked;
        }

        return $this->fillFromPool(
            $picked,
            $this->popularPool([$product->id], $product->category_id, true),
            $limit
        );
    }

    /**
     * @return Collection<int, Product>
     */
    public function similar(Product $product, int $limit = 8): Collection
    {
        $candidates = Product::query()
            ->active()
            ->with($this->relations())
            ->forCategory($product->category_id)
            ->where('id', '!=', $product->id)
            ->orderByDesc('is_featured')
            ->orderByDesc('review_count')
            ->limit(40)
            ->get();

        if ($candidates->count() < 8 && $product->category?->parent_id) {
            $extra = Product::query()
                ->active()
                ->with($this->relations())
                ->forCategory($product->category->parent_id)
                ->where('id', '!=', $product->id)
                ->whereNotIn('id', $candidates->modelKeys())
                ->limit(24)
                ->get();
            $candidates = $candidates->concat($extra);
        }

        $anchorPrice = (float) $product->effective_price;
        $anchorTokens = $this->tokens($product->name.' '.implode(' ', $product->keywords ?? []));
        $anchorKeywords = $this->normalizedSet($product->keywords ?? []);

        $ranked = $candidates
            ->map(function (Product $candidate) use ($product, $anchorPrice, $anchorTokens, $anchorKeywords) {
                $score = 0.0;
                if ((int) $candidate->category_id === (int) $product->category_id) {
                    $score += 22;
                } else {
                    $score += 10;
                }

                $price = (float) $candidate->effective_price;
                if ($anchorPrice > 0 && $price > 0) {
                    $ratio = $price / $anchorPrice;
                    if ($ratio >= 0.6 && $ratio <= 1.5) {
                        $score += 12;
                    } elseif ($ratio >= 0.4 && $ratio <= 2.0) {
                        $score += 6;
                    }
                }

                $score += count(array_intersect($anchorKeywords, $this->normalizedSet($candidate->keywords ?? []))) * 5;
                $score += count(array_intersect($anchorTokens, $this->tokens($candidate->name))) * 3;
                $score += $this->popularity($candidate);

                return ['product' => $candidate, 'score' => $score];
            })
            ->sortByDesc('score')
            ->values();

        return $ranked
            ->pluck('product')
            ->unique('id')
            ->take($limit)
            ->values();
    }

    /**
     * @param  list<int>  $productIds
     * @return Collection<int, Product>
     */
    public function completeCart(array $productIds, int $limit = 8): Collection
    {
        $productIds = $this->uniqueIds($productIds);
        if ($productIds === []) {
            return collect();
        }

        $cart = Product::query()
            ->with(['productRelations', 'category'])
            ->whereIn('id', $productIds)
            ->get();
        $cartCategoryIds = $cart->pluck('category_id')->map(fn ($id) => (int) $id)->unique()->all();
        $scores = $this->coOccurrenceScores($productIds);

        foreach ($cart as $item) {
            foreach ($this->relationsOf($item->id, $item) as $relation) {
                $relatedId = (int) $relation->related_product_id;
                if (in_array($relatedId, $productIds, true)) {
                    continue;
                }
                $bonus = $relation->type === ProductRelationType::Complementary ? 46.0 : 14.0;
                $scores[$relatedId] = ($scores[$relatedId] ?? 0) + $bonus;
            }
        }

        $picked = $this->hydrateRanked($scores, $productIds, $limit, $cartCategoryIds, true);
        if ($picked->count() >= $limit) {
            return $picked;
        }

        return $this->fillFromPool(
            $picked,
            $this->popularPool($productIds, $cartCategoryIds, true),
            $limit
        );
    }

    /**
     * @param  list<int>  $excludeIds
     * @return Collection<int, Product>
     */
    public function forYou(?User $user, array $excludeIds = [], int $limit = 10): Collection
    {
        $excludeIds = $this->uniqueIds($excludeIds);
        $scores = [];
        $affinityCategories = [];

        if ($user) {
            $history = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->where('orders.user_id', $user->id)
                ->where('orders.status', '!=', OrderStatus::Cancelled->value)
                ->selectRaw('order_items.product_id, products.category_id, COUNT(*) as freq')
                ->groupBy('order_items.product_id', 'products.category_id')
                ->get();

            foreach ($history as $row) {
                $productId = (int) $row->product_id;
                $affinityCategories[] = (int) $row->category_id;
                $scores[$productId] = ($scores[$productId] ?? 0) + 16 + min(8, (int) $row->freq);
            }

            foreach ($this->relationsForProducts($history->pluck('product_id')->all()) as $relation) {
                $relatedId = (int) $relation->related_product_id;
                $scores[$relatedId] = ($scores[$relatedId] ?? 0) + 22;
            }
        }

        $pool = Product::query()
            ->active()
            ->with($this->relations())
            ->when($excludeIds !== [], fn ($query) => $query->whereNotIn('id', $excludeIds))
            ->orderByDesc('is_featured')
            ->orderByDesc('review_count')
            ->limit(48)
            ->get();

        foreach ($pool as $product) {
            $score = $scores[$product->id] ?? 0;
            $score += $this->popularity($product);
            if (in_array((int) $product->category_id, $affinityCategories, true)) {
                $score += 10;
            }
            $scores[$product->id] = $score;
        }

        $picked = $this->hydrateRanked($scores, $excludeIds, $limit);
        if ($picked->count() >= $limit) {
            return $picked;
        }

        return $this->fillFromPool($picked, $pool, $limit);
    }

    /**
     * @param  list<int>  $orderedIds
     * @param  Collection<int, Product>  $products
     * @return Collection<int, Product>
     */
    public function applyOrder(Collection $products, array $orderedIds): Collection
    {
        if ($products->isEmpty() || $orderedIds === []) {
            return $products;
        }

        $byId = $products->keyBy(fn (Product $product) => (int) $product->id);
        $ordered = collect();
        foreach ($orderedIds as $id) {
            $product = $byId->get((int) $id);
            if ($product) {
                $ordered->push($product);
                $byId->forget((int) $id);
            }
        }

        return $ordered->concat($byId->values())->values();
    }

    /**
     * @return list<string>
     */
    public function relations(): array
    {
        return ['images', 'primaryImage', 'category'];
    }

    /**
     * @param  list<int>  $productIds
     * @return array<int, float>
     */
    private function coOccurrenceScores(array $productIds): array
    {
        $productIds = $this->uniqueIds($productIds);
        if ($productIds === []) {
            return [];
        }

        $rows = DB::table('order_items as a')
            ->join('order_items as b', 'a.order_id', '=', 'b.order_id')
            ->join('orders', 'orders.id', '=', 'a.order_id')
            ->whereIn('a.product_id', $productIds)
            ->whereNotIn('b.product_id', $productIds)
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->selectRaw('b.product_id, COUNT(*) as freq')
            ->groupBy('b.product_id')
            ->orderByDesc('freq')
            ->limit(48)
            ->get();

        $scores = [];
        foreach ($rows as $row) {
            $scores[(int) $row->product_id] = 8 + min(24, (int) $row->freq * 4);
        }

        return $scores;
    }

    /**
     * @return Collection<int, ProductRelation>
     */
    private function relationsOf(int $productId, ?Product $product = null): Collection
    {
        if ($product && $product->relationLoaded('productRelations')) {
            return $product->productRelations;
        }

        return ProductRelation::query()
            ->where('product_id', $productId)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @param  list<int|string>  $productIds
     * @return Collection<int, ProductRelation>
     */
    private function relationsForProducts(array $productIds): Collection
    {
        $productIds = $this->uniqueIds($productIds);
        if ($productIds === []) {
            return collect();
        }

        return ProductRelation::query()
            ->whereIn('product_id', $productIds)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @param  array<int, float>  $scores
     * @param  list<int>  $excludeIds
     * @param  int|list<int>|null  $avoidCategories
     * @return Collection<int, Product>
     */
    private function hydrateRanked(
        array $scores,
        array $excludeIds,
        int $limit,
        int|array|null $avoidCategories = null,
        bool $preferOtherCategory = false,
    ): Collection {
        foreach ($excludeIds as $id) {
            unset($scores[(int) $id]);
        }
        if ($scores === []) {
            return collect();
        }

        arsort($scores);
        $ids = array_keys(array_slice($scores, 0, 40, true));
        $products = Product::query()
            ->active()
            ->with($this->relations())
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $avoid = is_array($avoidCategories)
            ? array_map('intval', $avoidCategories)
            : ((int) $avoidCategories > 0 ? [(int) $avoidCategories] : []);

        return collect($ids)
            ->map(function (int $id) use ($products, $scores, $avoid, $preferOtherCategory) {
                $product = $products->get($id);
                if (! $product) {
                    return null;
                }
                $score = $scores[$id] ?? 0;
                if ($preferOtherCategory && $avoid !== [] && ! in_array((int) $product->category_id, $avoid, true)) {
                    $score += 10;
                } elseif ($preferOtherCategory && in_array((int) $product->category_id, $avoid, true)) {
                    $score -= 6;
                }
                $score += $this->popularity($product);

                return ['product' => $product, 'score' => $score];
            })
            ->filter()
            ->sortByDesc('score')
            ->pluck('product')
            ->unique('id')
            ->take($limit)
            ->values();
    }

    /**
     * @param  list<int>  $excludeIds
     * @param  int|list<int>|null  $avoidCategories
     * @return Collection<int, Product>
     */
    private function popularPool(array $excludeIds, int|array|null $avoidCategories = null, bool $otherCategory = false): Collection
    {
        $avoid = is_array($avoidCategories)
            ? array_map('intval', $avoidCategories)
            : ((int) $avoidCategories > 0 ? [(int) $avoidCategories] : []);

        return Product::query()
            ->active()
            ->with($this->relations())
            ->when($excludeIds !== [], fn ($query) => $query->whereNotIn('id', $excludeIds))
            ->when($otherCategory && $avoid !== [], fn ($query) => $query->whereNotIn('category_id', $avoid))
            ->orderByDesc('is_featured')
            ->orderByDesc('review_count')
            ->limit(16)
            ->get();
    }

    /**
     * @param  Collection<int, Product>  $picked
     * @param  Collection<int, Product>  $pool
     * @return Collection<int, Product>
     */
    private function fillFromPool(Collection $picked, Collection $pool, int $limit): Collection
    {
        $ids = $picked->pluck('id')->all();

        return $picked
            ->concat($pool->reject(fn (Product $product) => in_array($product->id, $ids, true)))
            ->unique('id')
            ->take($limit)
            ->values();
    }

    private function popularity(Product $product): float
    {
        return log(1 + (int) $product->review_count) * 2
            + ($product->is_featured ? 6.0 : 0.0)
            + ($product->has_discount ? 3.0 : 0.0);
    }

    /**
     * @param  list<mixed>  $values
     * @return list<string>
     */
    private function normalizedSet(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($value) => mb_strtolower(trim((string) $value)),
            $values
        ))));
    }

    /**
     * @return list<string>
     */
    private function tokens(string $text): array
    {
        $parts = preg_split('/[\s\-_,،.\/]+/u', mb_strtolower(trim($text))) ?: [];

        return array_values(array_filter($parts, fn (string $token) => mb_strlen($token) >= 2));
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<int>
     */
    private function uniqueIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($id) => (int) $id,
            $ids
        ), fn (int $id) => $id > 0)));
    }
}
