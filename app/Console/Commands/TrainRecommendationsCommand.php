<?php

namespace App\Console\Commands;

use App\Jobs\RefreshProductRecommendations;
use App\Models\Product;
use Illuminate\Console\Command;

class TrainRecommendationsCommand extends Command
{
    protected $signature = 'recommendations:train {--product=} {--limit=80}';

    protected $description = 'تدريب مساعد Gemini على علاقات التوصية وحسنت ترتيبها';

    public function handle(): int
    {
        $productId = (int) $this->option('product');
        $query = Product::query()->active()->orderByDesc('is_featured')->orderByDesc('id');

        if ($productId > 0) {
            $query->where('id', $productId);
        } else {
            $query->limit(max(1, (int) $this->option('limit')));
        }

        $ids = $query->pluck('id');
        if ($ids->isEmpty()) {
            $this->warn('لا توجد منتجات لتدريبها.');

            return self::SUCCESS;
        }

        foreach ($ids as $id) {
            RefreshProductRecommendations::dispatchSync((int) $id);
            $this->line('درّبت توصيات المنتج #'.$id);
        }

        $this->info('تم جدولة تدريب '.$ids->count().' منتجاً.');

        return self::SUCCESS;
    }
}
