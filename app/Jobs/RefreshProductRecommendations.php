<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\Ai\RecommendationTrainer;
use App\Services\Catalog\ProductRecommendationEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class RefreshProductRecommendations implements ShouldQueue
{
    use Queueable;

    public int $timeout = 90;

    public function __construct(public readonly int $productId) {}

    public function handle(
        RecommendationTrainer $trainer,
        ProductRecommendationEngine $engine,
    ): void {
        if (! Cache::add('reco:train:'.$this->productId, 1, now()->addMinutes(20))) {
            return;
        }

        try {
            $product = Product::query()
                ->with(['category', 'productRelations'])
                ->find($this->productId);
            if (! $product) {
                return;
            }

            $trainer->trainProduct($product);

            $bought = $engine->boughtTogether($product, 12);
            $trainer->rank('bought_together', [
                'product_id' => $product->id,
                'name' => $product->name,
                'category' => $product->category?->name,
            ], $bought);

            $similar = $engine->similar($product, 12);
            $trainer->rank('similar', [
                'product_id' => $product->id,
                'name' => $product->name,
                'category' => $product->category?->name,
            ], $similar);
        } catch (\Throwable $e) {
            Cache::forget('reco:train:'.$this->productId);
            throw $e;
        }
    }
}
