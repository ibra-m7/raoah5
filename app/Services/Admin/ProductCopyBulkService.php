<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Services\Ai\ProductCopyGenerator;
use App\Support\AiSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProductCopyBulkService
{
    public const CACHE_KEY = 'product-copy-bulk:status';

    public const IDS_KEY = 'product-copy-bulk:ids';

    public const INDEX_KEY = 'product-copy-bulk:index';

    public const LOCK_KEY = 'product-copy-bulk';

    /** Products processed per browser round-trip (concurrent Gemini calls). */
    public const CHUNK_SIZE = 3;

    /**
     * @return array{running: bool, total: int, ok: int, failed: int, processed: int, skipped: int, batch_id: string|null, finished_at: string|null, cancelled: bool}
     */
    public function status(): array
    {
        $status = Cache::get(self::CACHE_KEY);

        if (! is_array($status)) {
            return $this->emptyStatus();
        }

        if (($status['running'] ?? false) && ! Cache::has(self::IDS_KEY)) {
            Cache::lock(self::LOCK_KEY)->forceRelease();
            Cache::forget(self::CACHE_KEY);
            Cache::forget(self::INDEX_KEY);

            return $this->emptyStatus();
        }

        $ok = (int) ($status['ok'] ?? 0);
        $failed = (int) ($status['failed'] ?? 0);

        return [
            'running' => (bool) ($status['running'] ?? false),
            'total' => (int) ($status['total'] ?? 0),
            'ok' => $ok,
            'failed' => $failed,
            'processed' => $ok + $failed,
            'skipped' => (int) ($status['skipped'] ?? 0),
            'batch_id' => null,
            'finished_at' => isset($status['finished_at']) ? (string) $status['finished_at'] : null,
            'cancelled' => (bool) ($status['cancelled'] ?? false),
        ];
    }

    public function isRunning(): bool
    {
        return $this->status()['running'];
    }

    /**
     * @return array{running: bool, total: int, ok: int, failed: int, processed: int, skipped: int, batch_id: string|null, finished_at: string|null, cancelled: bool}
     */
    public function start(): array
    {
        if (! AiSettings::hasApiKey()) {
            throw new RuntimeException('missing_key');
        }

        if ($this->isRunning()) {
            throw new RuntimeException('already_running');
        }

        $lock = Cache::lock(self::LOCK_KEY, 3600);
        if (! $lock->get()) {
            throw new RuntimeException('already_running');
        }

        $productIds = Product::query()
            ->sellable()
            ->needsGeneratedCopy()
            ->orderBy('id')
            ->pluck('id');

        $sellableCount = Product::query()->sellable()->count();

        if ($sellableCount === 0) {
            $lock->release();

            throw new RuntimeException('no_products');
        }

        $skipped = $sellableCount - $productIds->count();

        if ($productIds->isEmpty()) {
            $lock->release();

            throw new RuntimeException('all_complete');
        }

        try {
            app(ProductCopyGenerator::class)->warmCaches();
        } catch (Throwable) {
            // Cache warm-up is best-effort.
        }

        $status = [
            'running' => true,
            'total' => $productIds->count(),
            'ok' => 0,
            'failed' => 0,
            'processed' => 0,
            'skipped' => $skipped,
            'finished_at' => null,
            'cancelled' => false,
        ];

        Cache::put(self::CACHE_KEY, $status, 3600);
        Cache::put(self::IDS_KEY, $productIds->values()->all(), 3600);
        Cache::put(self::INDEX_KEY, 0, 3600);

        return $this->status();
    }

    /**
     * @return array{running: bool, total: int, ok: int, failed: int, processed: int, skipped: int, batch_id: string|null, finished_at: string|null, cancelled: bool}
     */
    public function processChunk(int $limit = self::CHUNK_SIZE): array
    {
        $status = Cache::get(self::CACHE_KEY);
        if (! is_array($status) || ! ($status['running'] ?? false)) {
            return $this->status();
        }

        if ($status['cancelled'] ?? false) {
            $this->finish(cancelled: true);

            return $this->status();
        }

        $ids = Cache::get(self::IDS_KEY, []);
        if (! is_array($ids) || $ids === []) {
            $this->finish();

            return $this->status();
        }

        $index = (int) Cache::get(self::INDEX_KEY, 0);
        if ($index >= count($ids)) {
            $this->finish();

            return $this->status();
        }

        if ($this->isCancelled()) {
            $this->finish(cancelled: true);

            return $this->status();
        }

        $limit = max(1, min($limit, self::CHUNK_SIZE));
        $batchIds = array_map('intval', array_slice($ids, $index, $limit));
        $this->processBatch($batchIds);

        $index += count($batchIds);
        Cache::put(self::INDEX_KEY, $index, 3600);

        if ($index >= count($ids)) {
            $this->finish();
        }

        return $this->status();
    }

    /**
     * @param  list<int>  $productIds
     */
    private function processBatch(array $productIds): void
    {
        if ($productIds === []) {
            return;
        }

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $productService = app(ProductService::class);
        $generator = app(ProductCopyGenerator::class);
        $inputs = [];

        foreach ($productIds as $productId) {
            $product = $products->get($productId);
            if (! $product) {
                self::recordFailure();

                continue;
            }

            if (! $productService->productNeedsGeneratedCopy($product)) {
                self::recordSuccess();

                continue;
            }

            $inputs[$productId] = [
                'name' => $product->name,
                'category_id' => $product->category_id,
                'description' => $product->description,
                'weight_label' => $product->weight_label,
                'quantity_label' => $product->quantity_label,
                'piece_count' => $product->piece_count,
            ];
        }

        if ($inputs === []) {
            return;
        }

        try {
            $copies = $generator->generateMany($inputs, fast: true);
        } catch (Throwable $e) {
            Log::warning('product.copy.bulk_batch_failed', [
                'reason' => mb_substr($e->getMessage(), 0, 180),
                'count' => count($inputs),
            ]);

            foreach (array_keys($inputs) as $productId) {
                self::recordFailure();
            }

            return;
        }

        foreach ($inputs as $productId => $input) {
            $product = $products->get($productId);
            $copy = $copies[$productId] ?? null;

            if (! $product || ! is_array($copy)) {
                self::recordFailure();

                continue;
            }

            try {
                unset($copy['meta']);
                $productService->applyGeneratedCopy($product, $copy, onlyEmpty: true);
                self::recordSuccess();
            } catch (Throwable $e) {
                Log::warning('product.copy.bulk_failed', [
                    'product_id' => $productId,
                    'reason' => mb_substr($e->getMessage(), 0, 180),
                ]);
                self::recordFailure();
            }
        }
    }

    /**
     * @return array{running: bool, total: int, ok: int, failed: int, processed: int, skipped: int, batch_id: string|null, finished_at: string|null, cancelled: bool, percent: int}
     */
    public function statusWithPercent(): array
    {
        $status = $this->status();
        $total = max(1, (int) $status['total']);
        $processed = (int) $status['processed'];

        return [
            ...$status,
            'percent' => $status['running']
                ? (int) round(($processed / $total) * 100)
                : ($status['finished_at'] ? 100 : 0),
        ];
    }

    /**
     * @return array{running: bool, total: int, ok: int, failed: int, processed: int, skipped: int, batch_id: string|null, finished_at: string|null, cancelled: bool}
     */
    public function cancel(): array
    {
        $status = $this->status();

        if (! $status['running']) {
            throw new RuntimeException('not_running');
        }

        $current = Cache::get(self::CACHE_KEY);
        if (is_array($current)) {
            $current['cancelled'] = true;
            $current['running'] = false;
            $current['finished_at'] = now()->toIso8601String();
            Cache::put(self::CACHE_KEY, $current, 3600);
        }

        Cache::forget(self::IDS_KEY);
        Cache::forget(self::INDEX_KEY);
        Cache::lock(self::LOCK_KEY)->forceRelease();

        return $this->status();
    }

    public static function recordSuccess(): void
    {
        self::bump('ok');
    }

    public static function recordFailure(): void
    {
        self::bump('failed');
    }

    public static function shouldTrack(): bool
    {
        $status = Cache::get(self::CACHE_KEY);

        return is_array($status) && ($status['running'] ?? false);
    }

    private function finish(bool $cancelled = false): void
    {
        $current = Cache::get(self::CACHE_KEY);
        if (is_array($current)) {
            $current['running'] = false;
            $current['finished_at'] = now()->toIso8601String();
            $current['cancelled'] = $cancelled || (bool) ($current['cancelled'] ?? false);
            Cache::put(self::CACHE_KEY, $current, 3600);
        }

        Cache::forget(self::IDS_KEY);
        Cache::forget(self::INDEX_KEY);
        Cache::lock(self::LOCK_KEY)->forceRelease();
    }

    private function isCancelled(): bool
    {
        $status = Cache::get(self::CACHE_KEY);

        return is_array($status) && ($status['cancelled'] ?? false);
    }

    private static function bump(string $field): void
    {
        $status = Cache::get(self::CACHE_KEY);
        if (! is_array($status) || ! ($status['running'] ?? false)) {
            return;
        }

        $status[$field] = ((int) ($status[$field] ?? 0)) + 1;
        $status['processed'] = (int) ($status['ok'] ?? 0) + (int) ($status['failed'] ?? 0);
        Cache::put(self::CACHE_KEY, $status, 3600);
    }

    /**
     * @return array{running: bool, total: int, ok: int, failed: int, processed: int, skipped: int, batch_id: string|null, finished_at: string|null, cancelled: bool}
     */
    private function emptyStatus(): array
    {
        return [
            'running' => false,
            'total' => 0,
            'ok' => 0,
            'failed' => 0,
            'processed' => 0,
            'skipped' => 0,
            'batch_id' => null,
            'finished_at' => null,
            'cancelled' => false,
        ];
    }
}
