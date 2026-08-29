<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\Admin\ProductCopyBulkService;
use App\Services\Admin\ProductService;
use App\Services\Ai\ProductCopyGenerator;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GenerateProductCopyJob implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public int $timeout = 90;

    public int $tries = 2;

    public int $backoff = 30;

    public function __construct(public readonly int $productId) {}

    public function handle(
        ProductCopyGenerator $generator,
        ProductService $products,
    ): void {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $product = Product::query()->find($this->productId);
        if (! $product) {
            if (ProductCopyBulkService::shouldTrack()) {
                ProductCopyBulkService::recordFailure();
            }

            return;
        }

        try {
            $copy = $generator->generate([
                'name' => $product->name,
                'category_id' => $product->category_id,
                'description' => $product->description,
                'weight_label' => $product->weight_label,
                'quantity_label' => $product->quantity_label,
                'piece_count' => $product->piece_count,
            ], fast: true);

            $products->applyGeneratedCopy($product, $copy);

            if (ProductCopyBulkService::shouldTrack()) {
                ProductCopyBulkService::recordSuccess();
            }
        } catch (Throwable $e) {
            if (ProductCopyBulkService::shouldTrack()) {
                ProductCopyBulkService::recordFailure();
            }

            Log::warning('product.copy.job_failed', [
                'product_id' => $this->productId,
                'reason' => mb_substr($e->getMessage(), 0, 180),
            ]);

            if ($e instanceof RuntimeException && $e->getMessage() === 'missing_key') {
                $this->batch()?->cancel();

                throw $e;
            }
        }
    }
}
