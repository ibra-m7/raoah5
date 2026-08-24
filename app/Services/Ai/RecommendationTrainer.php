<?php

namespace App\Services\Ai;

use App\Enums\ProductRelationType;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Support\AiSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecommendationTrainer
{
    public function __construct(
        private readonly GeminiClient $gemini,
    ) {}

    /**
     * @param  Collection<int, Product>  $candidates
     * @return list<int>|null
     */
    public function cachedRank(string $mechanism, string $contextKey, Collection $candidates): ?array
    {
        $cached = Cache::get($this->rankKey($mechanism, $contextKey));
        if (! is_array($cached) || $cached === []) {
            return null;
        }

        $allowed = $candidates->pluck('id')->map(fn ($id) => (int) $id)->all();

        return array_values(array_filter(
            array_map('intval', $cached),
            fn (int $id) => in_array($id, $allowed, true)
        ));
    }

    /**
     * @param  Collection<int, Product>  $candidates
     * @return list<int>
     */
    public function rank(string $mechanism, array $anchor, Collection $candidates): array
    {
        if ($candidates->isEmpty() || ! AiSettings::hasApiKey()) {
            return [];
        }

        $contextKey = $this->contextKey($anchor, $candidates);
        $cached = $this->cachedRank($mechanism, $contextKey, $candidates);
        if ($cached !== null && $cached !== []) {
            return $cached;
        }

        try {
            $raw = $this->gemini->generateJson(
                $this->systemPrompt(),
                $this->rankPrompt($mechanism, $anchor, $candidates)
            );
        } catch (Throwable $e) {
            Log::warning('reco.ai.rank_failed', [
                'mechanism' => $mechanism,
                'error' => mb_substr($e->getMessage(), 0, 180),
            ]);

            return [];
        }

        $ids = $this->parseIdList($raw, $this->mechanismField($mechanism), $candidates);
        if ($ids !== []) {
            Cache::put($this->rankKey($mechanism, $contextKey), $ids, now()->addHours(12));
        }

        return $ids;
    }

    public function trainProduct(Product $product): void
    {
        if (! AiSettings::hasApiKey()) {
            return;
        }

        $product->loadMissing('category');
        $candidates = $this->trainingPool($product);
        if ($candidates->isEmpty()) {
            return;
        }

        try {
            $raw = $this->gemini->generateJson(
                $this->systemPrompt(),
                $this->trainPrompt($product, $candidates)
            );
        } catch (Throwable $e) {
            Log::warning('reco.ai.train_failed', [
                'product_id' => $product->id,
                'error' => mb_substr($e->getMessage(), 0, 180),
            ]);

            return;
        }

        $complementary = $this->parseIdList($raw, 'complementary_ids', $candidates);
        $similar = $this->parseIdList($raw, 'similar_ids', $candidates);

        $this->persistComplementary($product, $complementary);
        $this->storeRank('bought_together', ['product_id' => $product->id], $complementary);
        $this->storeRank('similar', ['product_id' => $product->id], $similar);
    }

    /**
     * @return Collection<int, Product>
     */
    public function trainingPool(Product $product): Collection
    {
        $same = Product::query()
            ->active()
            ->with('category')
            ->forCategory($product->category_id)
            ->where('id', '!=', $product->id)
            ->orderByDesc('is_featured')
            ->limit(12)
            ->get();

        $others = Product::query()
            ->active()
            ->with('category')
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($query) => $query->where('category_id', '!=', $product->category_id))
            ->orderByDesc('is_featured')
            ->orderByDesc('review_count')
            ->limit(20)
            ->get();

        return $same->concat($others)->unique('id')->take(28)->values();
    }

    /**
     * @param  list<int>  $ids
     */
    private function persistComplementary(Product $product, array $ids): void
    {
        ProductRelation::query()
            ->where('product_id', $product->id)
            ->where('type', ProductRelationType::Complementary)
            ->where('source', 'ai')
            ->delete();

        foreach (array_slice($ids, 0, 6) as $index => $relatedId) {
            if ($relatedId === (int) $product->id) {
                continue;
            }

            $existsManual = ProductRelation::query()
                ->where('product_id', $product->id)
                ->where('related_product_id', $relatedId)
                ->where('type', ProductRelationType::Complementary)
                ->where('source', 'manual')
                ->exists();
            if ($existsManual) {
                continue;
            }

            ProductRelation::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'related_product_id' => $relatedId,
                    'type' => ProductRelationType::Complementary,
                ],
                [
                    'sort_order' => $index,
                    'source' => 'ai',
                ]
            );
        }
    }

    /**
     * @param  list<int>  $ids
     */
    private function storeRank(string $mechanism, array $anchor, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        Cache::put(
            $this->rankKey($mechanism, $this->contextKey($anchor, collect())),
            $ids,
            now()->addHours(12)
        );
    }

    /**
     * @param  Collection<int, Product>  $candidates
     * @return list<int>
     */
    private function parseIdList(string $raw, string $field, Collection $candidates): array
    {
        $decoded = json_decode($raw, true);
        if (! is_array($decoded) && preg_match('/\{.*\}/s', $raw, $match) === 1) {
            $decoded = json_decode($match[0], true);
        }
        if (! is_array($decoded)) {
            return [];
        }

        $values = $decoded[$field] ?? $decoded['product_ids'] ?? [];
        if (! is_array($values)) {
            return [];
        }

        $allowed = $candidates->pluck('id')->map(fn ($id) => (int) $id)->all();
        $ids = [];
        foreach ($values as $value) {
            $id = (int) $value;
            if ($id > 0 && in_array($id, $allowed, true)) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  Collection<int, Product>  $candidates
     */
    private function rankPrompt(string $mechanism, array $anchor, Collection $candidates): string
    {
        $lines = [
            'الآلية المطلوبة: '.$this->mechanismLabel($mechanism),
            $this->anchorBlock($anchor),
            'المرشحون (رتّب المعرّفات فقط من هذه القائمة):',
            $this->catalogLines($candidates),
            'أرجع JSON فقط: {"'.$this->mechanismField($mechanism).'":[1,2,3]}',
        ];

        return implode("\n", array_filter($lines));
    }

    /**
     * @param  Collection<int, Product>  $candidates
     */
    private function trainPrompt(Product $product, Collection $candidates): string
    {
        return implode("\n", [
            'الآلية المطلوبة: تدريب علاقات المنتج',
            $this->anchorBlock([
                'product_id' => $product->id,
                'name' => $product->name,
                'category' => $product->category?->name,
                'price' => (float) $product->effective_price,
                'keywords' => $product->keywords ?? [],
            ]),
            'المرشحون:',
            $this->catalogLines($candidates),
            'أرجع JSON فقط: {"complementary_ids":[...],"similar_ids":[...]}',
            'complementary_ids: حتى 6 منتجات تُشترى معه أو تكمل السلة من فئة مختلفة.',
            'similar_ids: حتى 6 بدائل أو منتجات مشابهة من نفس الحاجة.',
        ]);
    }

    /**
     * @param  Collection<int, Product>  $candidates
     */
    private function catalogLines(Collection $candidates): string
    {
        return $candidates->map(function (Product $product) {
            $keywords = collect($product->keywords ?? [])->take(4)->implode('، ');

            return sprintf(
                '[%d] %s | %s | %.2f | %s',
                $product->id,
                $product->name,
                $product->category?->name ?? 'عام',
                (float) $product->effective_price,
                $keywords
            );
        })->implode("\n");
    }

    private function anchorBlock(array $anchor): string
    {
        if ($anchor === []) {
            return '';
        }

        $lines = ['سياق المنتج/السلة:'];
        foreach ($anchor as $key => $value) {
            if (is_array($value)) {
                $value = implode('، ', array_map('strval', $value));
            }
            $lines[] = $key.': '.$value;
        }

        return implode("\n", $lines);
    }

    private function mechanismField(string $mechanism): string
    {
        return match ($mechanism) {
            'similar' => 'similar_ids',
            'complete_cart' => 'complete_cart_ids',
            'for_you' => 'suggested_ids',
            default => 'bought_together_ids',
        };
    }

    private function mechanismLabel(string $mechanism): string
    {
        return match ($mechanism) {
            'similar' => 'منتجات مشابهة',
            'complete_cart' => 'منتجات تكمل السلة',
            'for_you' => 'منتجات مقترحة لك',
            default => 'يُشترى معه غالباً',
        };
    }

    /**
     * @param  Collection<int, Product>  $candidates
     */
    public function contextKey(array $anchor, Collection $candidates): string
    {
        return md5(json_encode([
            $anchor['product_id'] ?? $anchor['product_ids'] ?? $anchor['user_id'] ?? 'x',
            $candidates->pluck('id')->all(),
        ], JSON_UNESCAPED_UNICODE) ?: '');
    }

    private function rankKey(string $mechanism, string $contextKey): string
    {
        return 'reco:rank:'.$mechanism.':'.$contextKey;
    }

    private function systemPrompt(): string
    {
        return <<<'TXT'
أنت مساعد تسويق وتوصية منتجات لمتجر بقالة سعودي اسمه «روعة الخمسة».
تعمل وفق معايير المتاجر العالمية (Amazon / Instacart / Noon) بدون اختلاق منتجات.

أربع آليات ثابتة:

1) يُشترى معه غالباً (Frequently bought together)
- منتجات تُكمّل المنتج الحالي في نفس الطلب، وليست بديلاً عنه.
- فضّل فئة مختلفة: خبز مع جبن، شاي مع سكر، منظف مع إسفنج.
- لا تقترح نفس المنتج أو حجماً مطابقاً منه.

2) منتجات مشابهة (Similar items)
- بدائل لنفس الحاجة: نفس الفئة أو فئة قريبة، سعر قريب، كلمات مفتاحية متشابهة.
- مثال: حليب كامل بجانب حليب قليل الدسم.

3) منتجات تكمل سلتك (Complete the cart)
- عناصر ناقصة لطلب متكامل من فئات غير موجودة في السلة.
- لا تكرر ما في السلة، ولا تملأ الصف ببدائل لنفس الصنف.

4) منتجات مقترحة لك (Suggested for you)
- مزيج شخصي: إعادة شراء معتادة + مكملات + منتجات مميزة شائعة.
- في البقالة إعادة الشراء صحيحة ومطلوبة.

قواعد عامة:
- اختر فقط معرّفات من القائمة المرفقة.
- رتّب الأقوى أولاً.
- أرجع JSON فقط بدون شرح.
TXT;
    }
}
