<?php

namespace App\Services\Admin;

use App\Jobs\GenerateProductCopyJob;
use App\Models\Product;
use App\Support\AiSettings;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class ProductCopyBulkService
{
    public const CACHE_KEY = 'product-copy-bulk:status';

    public const LOCK_KEY = 'product-copy-bulk';

    /**
     * @return array{running: bool, total: int, ok: int, failed: int, processed: int, batch_id: string|null, finished_at: string|null, cancelled: bool}
     */
    public function status(): array
    {
        $status = Cache::get(self::CACHE_KEY);

        if (! is_array($status)) {
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
            'batch_id' => isset($status['batch_id']) ? (string) $status['batch_id'] : null,
            'finished_at' => isset($status['finished_at']) ? (string) $status['finished_at'] : null,
            'cancelled' => (bool) ($status['cancelled'] ?? false),
        ];
    }

    public function isRunning(): bool
    {
        return $this->status()['running'];
    }

    /**
     * @return array{running: bool, total: int, ok: int, failed: int, processed: int, batch_id: string|null, finished_at: string|null}
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
            ->orderBy('id')
            ->pluck('id');

        if ($productIds->isEmpty()) {
            $lock->release();

            throw new RuntimeException('no_products');
        }

        $status = [
            'running' => true,
            'total' => $productIds->count(),
            'ok' => 0,
            'failed' => 0,
            'processed' => 0,
            'batch_id' => null,
            'finished_at' => null,
            'cancelled' => false,
        ];
        Cache::put(self::CACHE_KEY, $status, 3600);

        $jobs = $productIds
            ->map(fn ($id) => new GenerateProductCopyJob((int) $id))
            ->all();

        try {
            $batch = Bus::batch($jobs)
                ->name('product-copy-bulk')
                ->allowFailures()
                ->finally(function (Batch $batch) {
                    Cache::lock(self::LOCK_KEY)->forceRelease();

                    $current = Cache::get(self::CACHE_KEY);
                    if (! is_array($current) || ! isset($current['total'])) {
                        return;
                    }

                    $current['running'] = false;
                    $current['finished_at'] = now()->toIso8601String();
                    Cache::put(self::CACHE_KEY, $current, 3600);
                })
                ->dispatch();

            $status['batch_id'] = $batch->id;
            Cache::put(self::CACHE_KEY, $status, 3600);
        } catch (Throwable $e) {
            $lock->release();
            Cache::forget(self::CACHE_KEY);

            throw $e;
        }

        return $this->status();
    }

    /**
     * @return array{running: bool, total: int, ok: int, failed: int, processed: int, batch_id: string|null, finished_at: string|null, cancelled: bool}
     */
    public function cancel(): array
    {
        $status = $this->status();

        if (! $status['running']) {
            throw new RuntimeException('not_running');
        }

        $batchId = $status['batch_id'];
        if ($batchId) {
            $batch = Bus::findBatch($batchId);
            $batch?->cancel();
        }

        Cache::lock(self::LOCK_KEY)->forceRelease();
        Cache::forget(self::CACHE_KEY);

        return $this->emptyStatus();
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
     * @return array{running: bool, total: int, ok: int, failed: int, processed: int, batch_id: string|null, finished_at: string|null}
     */
    private function emptyStatus(): array
    {
        return [
            'running' => false,
            'total' => 0,
            'ok' => 0,
            'failed' => 0,
            'processed' => 0,
            'batch_id' => null,
            'finished_at' => null,
            'cancelled' => false,
        ];
    }
}
