<?php

namespace App\Services\Ai;

use App\Models\Category;
use App\Services\Admin\CategoryService;
use App\Support\AiSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProductCopyGenerator
{
    private const CATEGORY_CACHE_KEY = 'ai:product_category_options';

    private const CATEGORY_CACHE_TTL = 3600;

    private const COPY_CACHE_TTL = 1800;

    public function __construct(
        private readonly GeminiClient $gemini,
        private readonly ProductNameParser $nameParser,
    ) {}

    /**
     * @param  array{name?: string, category_id?: int|string|null, description?: string|null, weight_label?: string|null, quantity_label?: string|null, piece_count?: int|string|null}  $input
     * @return array{
     *     benefits: list<string>,
     *     keywords: list<string>,
     *     usage_instructions: string,
     *     description: string,
     *     category_id: int|null,
     *     price: float|null,
     *     stock: int|null,
     *     piece_count: int|null,
     *     weight_label: string,
     *     quantity_label: string,
     *     meta: array{cached: bool, source: string}
     * }
     */
    public function generate(array $input, bool $fast = true): array
    {
        if (! AiSettings::hasApiKey()) {
            throw new RuntimeException('missing_key');
        }

        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('missing_name');
        }

        if (mb_strlen($name) < 3) {
            throw new RuntimeException('missing_name');
        }

        $hints = $this->nameParser->parse($name);
        $categoryId = is_numeric($input['category_id'] ?? null) ? (int) $input['category_id'] : null;
        $cacheKey = $this->cacheKey($name, $input, $categoryId, $hints);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            $cached['meta'] = ['cached' => true, 'source' => 'cache'];

            return $cached;
        }

        $categories = $this->categoryOptionsFor($categoryId, $hints['search_tokens']);
        $system = $categoryId
            ? $this->compactSystemPrompt($categoryId, $categories)
            : $this->systemPrompt($categories);

        try {
            $raw = $fast
                ? $this->gemini->generateJsonFast($system, $this->userPrompt($name, $input, $hints))
                : $this->gemini->generateJson($system, $this->userPrompt($name, $input, $hints));
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage() !== '' ? $e->getMessage() : 'gemini_failed', 0, $e);
        }

        $parsed = $this->parse($raw, $name, $categories, $categoryId);
        $merged = $this->merge($parsed, $hints, $input, $categoryId);
        $merged['meta'] = ['cached' => false, 'source' => 'ai'];

        Cache::put($cacheKey, $merged, self::COPY_CACHE_TTL);

        return $merged;
    }

    /**
     * Generate copy for multiple products concurrently. Same quality path as generate().
     *
     * @param  array<int|string, array{name?: string, category_id?: int|string|null, description?: string|null, weight_label?: string|null, quantity_label?: string|null, piece_count?: int|string|null}>  $inputs
     * @return array<int|string, array{
     *     benefits: list<string>,
     *     keywords: list<string>,
     *     usage_instructions: string,
     *     description: string,
     *     category_id: int|null,
     *     price: float|null,
     *     stock: int|null,
     *     piece_count: int|null,
     *     weight_label: string,
     *     quantity_label: string,
     *     meta: array{cached: bool, source: string}
     * }>
     */
    public function generateMany(array $inputs, bool $fast = true): array
    {
        if (! AiSettings::hasApiKey()) {
            throw new RuntimeException('missing_key');
        }

        $results = [];
        $pending = [];

        foreach ($inputs as $key => $input) {
            $name = trim((string) ($input['name'] ?? ''));
            if ($name === '' || mb_strlen($name) < 3) {
                continue;
            }

            $hints = $this->nameParser->parse($name);
            $categoryId = is_numeric($input['category_id'] ?? null) ? (int) $input['category_id'] : null;
            $cacheKey = $this->cacheKey($name, $input, $categoryId, $hints);

            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                $cached['meta'] = ['cached' => true, 'source' => 'cache'];
                $results[$key] = $cached;

                continue;
            }

            $categories = $this->categoryOptionsFor($categoryId, $hints['search_tokens']);
            $system = $categoryId
                ? $this->compactSystemPrompt($categoryId, $categories)
                : $this->systemPrompt($categories);

            $pending[$key] = [
                'name' => $name,
                'input' => $input,
                'hints' => $hints,
                'category_id' => $categoryId,
                'categories' => $categories,
                'cache_key' => $cacheKey,
                'system' => $system,
                'user' => $this->userPrompt($name, $input, $hints),
            ];
        }

        if ($pending === []) {
            return $results;
        }

        $rawMap = $fast
            ? $this->gemini->generateJsonFastMany(
                collect($pending)
                    ->mapWithKeys(fn (array $row, $key) => [$key => [
                        'system' => $row['system'],
                        'user' => $row['user'],
                    ]])
                    ->all()
            )
            : [];

        foreach ($pending as $key => $row) {
            try {
                $raw = $rawMap[$key] ?? null;
                if (! is_string($raw) || $raw === '') {
                    $raw = $fast
                        ? $this->gemini->generateJsonFast($row['system'], $row['user'])
                        : $this->gemini->generateJson($row['system'], $row['user']);
                }

                $parsed = $this->parse($raw, $row['name'], $row['categories'], $row['category_id']);
                $merged = $this->merge($parsed, $row['hints'], $row['input'], $row['category_id']);
                $merged['meta'] = ['cached' => false, 'source' => 'ai'];
                Cache::put($row['cache_key'], $merged, self::COPY_CACHE_TTL);
                $results[$key] = $merged;
            } catch (Throwable $e) {
                Log::warning('gemini.product_copy.batch_item_failed', [
                    'key' => $key,
                    'reason' => mb_substr($e->getMessage(), 0, 180),
                ]);
            }
        }

        return $results;
    }

    public function warmCaches(): void
    {
        $this->categoryOptions();
    }

    /**
     * @param  array{name?: string, category_id?: int|string|null, description?: string|null, weight_label?: string|null, quantity_label?: string|null, piece_count?: int|string|null}  $input
     * @param  array{weight_label: string, piece_count: int|null, quantity_label: string, search_tokens: list<string>}  $hints
     */
    private function userPrompt(string $name, array $input, array $hints): string
    {
        $lines = ['اسم المنتج: '.$name];

        if ($hints['weight_label'] !== '') {
            $lines[] = 'وزن مستخرج من الاسم: '.$hints['weight_label'];
        }
        if ($hints['piece_count'] !== null) {
            $lines[] = 'عدد الحبات المستخرج: '.$hints['piece_count'];
        }

        $description = trim((string) ($input['description'] ?? ''));
        if ($description !== '') {
            $lines[] = 'الوصف الحالي (يمكن تحسينه): '.$description;
        }

        $weight = trim((string) ($input['weight_label'] ?? ''));
        if ($weight !== '') {
            $lines[] = 'الوزن المدخل: '.$weight;
        }

        $quantity = trim((string) ($input['quantity_label'] ?? ''));
        if ($quantity !== '') {
            $lines[] = 'وصف الكمية المدخل: '.$quantity;
        }

        $pieces = (int) ($input['piece_count'] ?? 0);
        if ($pieces > 1) {
            $lines[] = 'عدد الحبات المدخل: '.$pieces;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $tokens
     * @return Collection<int, Category>
     */
    private function categoryOptionsFor(?int $categoryId, array $tokens): Collection
    {
        $all = $this->categoryOptions();

        if ($categoryId) {
            $current = $all->firstWhere('id', $categoryId);
            if ($current) {
                $parentId = (int) ($current->parent_id ?? 0);
                $scoped = $all->filter(function (Category $category) use ($current, $parentId) {
                    if ((int) $category->id === (int) $current->id) {
                        return true;
                    }

                    if ($parentId > 0 && (int) ($category->parent_id ?? 0) === $parentId) {
                        return true;
                    }

                    return (int) ($category->depth ?? 0) >= 2;
                });

                if ($scoped->count() >= 5) {
                    return $scoped->values();
                }
            }
        }

        if ($tokens === []) {
            return $all->take(35)->values();
        }

        $ranked = $all
            ->map(function (Category $category) use ($tokens) {
                $haystack = mb_strtolower((string) ($category->path_label ?? $category->name));
                $score = 0;
                foreach ($tokens as $token) {
                    if (str_contains($haystack, $token)) {
                        $score += 3;
                    }
                }
                if ((int) ($category->depth ?? 0) >= 2) {
                    $score += 1;
                }

                return ['category' => $category, 'score' => $score];
            })
            ->sortByDesc('score')
            ->values();

        $top = $ranked
            ->filter(fn (array $row) => $row['score'] > 0)
            ->take(28)
            ->pluck('category');

        if ($top->count() < 12) {
            $top = $ranked->take(28)->pluck('category');
        }

        if ($categoryId && ! $top->contains('id', $categoryId)) {
            $current = $all->firstWhere('id', $categoryId);
            if ($current) {
                $top->prepend($current);
            }
        }

        return $top->unique('id')->values();
    }

    /**
     * @return Collection<int, Category>
     */
    private function categoryOptions(): Collection
    {
        return Cache::remember(
            self::CATEGORY_CACHE_KEY,
            self::CATEGORY_CACHE_TTL,
            fn () => app(CategoryService::class)->productCategoryOptions(),
        );
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function systemPrompt(Collection $categories): string
    {
        return $this->basePrompt($this->categoryJson($categories), true);
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function compactSystemPrompt(int $categoryId, Collection $categories): string
    {
        $current = $categories->firstWhere('id', $categoryId);
        $path = $current
            ? (string) ($current->path_label ?? $current->name)
            : 'غير محدد';

        return $this->basePrompt(
            json_encode([
                ['id' => $categoryId, 'path' => $path],
            ], JSON_UNESCAPED_UNICODE),
            false,
        );
    }

    private function basePrompt(string $categoryJson, bool $mustPickCategory): string
    {
        $categoryRule = $mustPickCategory
            ? '- category_id: رقم تصنيف من القائمة فقط. اختر أدق تصنيف فرعي.'
            : '- category_id: أبقِ نفس رقم التصنيف المحدد إن كان مناسباً، وإلا اختر الأقرب من القائمة.';

        return <<<TXT
أنت مساعد إضافة منتجات لمتجر بقالة سعودي اسمه «روعة الخمسة».
أرجع JSON فقط بهذا الشكل:
{"description":"...","category_id":123,"price":12.5,"stock":20,"piece_count":null,"weight_label":"","quantity_label":"","benefits":["..."],"keywords":["..."],"usage_instructions":"..."}
- description: وصف عربي قصير من سطرين إلى ثلاثة، واقعي بدون مبالغة.
{$categoryRule}
- price: سعر تجزئة تقريبي بالريال السعودي، رقم فقط.
- stock: مخزون ابتدائي بين 15 و 60.
- piece_count: عدد الحبات إن وُجد في الاسم، وإلا null.
- weight_label: الوزن أو الحجم مثل «500 مل» أو «1 كجم»، وإلا نص فارغ.
- quantity_label: وصف كمية مختصر إن لزم، وإلا نص فارغ.
- benefits: 3 إلى 5 فوائد قصيرة.
- keywords: 6 إلى 10 كلمات بحث عربية شائعة.
- usage_instructions: طريقة استخدام عملية من سطرين إلى ثلاثة.
لا تختلق ادعاءات طبية. التصنيفات المتاحة (JSON): {$categoryJson}
TXT;
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function categoryJson(Collection $categories): string
    {
        return $categories
            ->map(fn (Category $category) => [
                'id' => (int) $category->id,
                'path' => (string) ($category->path_label ?? $category->name),
            ])
            ->values()
            ->toJson(JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return array{
     *     benefits: list<string>,
     *     keywords: list<string>,
     *     usage_instructions: string,
     *     description: string,
     *     category_id: int|null,
     *     price: float|null,
     *     stock: int|null,
     *     piece_count: int|null,
     *     weight_label: string,
     *     quantity_label: string
     * }
     */
    private function parse(string $raw, string $name, Collection $categories, ?int $lockedCategoryId): array
    {
        $decoded = json_decode($raw, true);
        if (! is_array($decoded) && preg_match('/\{.*\}/s', $raw, $match) === 1) {
            $decoded = json_decode($match[0], true);
        }
        if (! is_array($decoded)) {
            Log::warning('gemini.product_copy.invalid_json', [
                'snippet' => mb_substr($raw, 0, 180),
            ]);
            throw new RuntimeException('invalid_json');
        }

        $benefits = $this->stringList($decoded['benefits'] ?? []);
        $keywords = $this->stringList($decoded['keywords'] ?? []);
        $usage = trim((string) ($decoded['usage_instructions'] ?? $decoded['usage'] ?? ''));
        $description = trim((string) ($decoded['description'] ?? ''));

        if ($benefits === []) {
            $benefits = ['مناسب للاستخدام اليومي', 'خيار عملي للمطبخ'];
        }
        if ($keywords === []) {
            $keywords = array_values(array_unique([$name, ...$this->nameParser->parse($name)['search_tokens']]));
        }
        if ($usage === '') {
            $usage = 'يُستخدم حسب الحاجة اليومية مع اتباع تعليمات المنتج على العبوة.';
        }
        if ($description === '') {
            $description = $name.' منتج مناسب للاستخدام اليومي من متجر روعة الخمسة.';
        }

        $categoryId = $lockedCategoryId ?: $this->resolveCategoryId(
            $decoded['category_id'] ?? null,
            $decoded['category'] ?? null,
            $categories,
        );

        return [
            'benefits' => array_slice($benefits, 0, 6),
            'keywords' => array_slice($keywords, 0, 12),
            'usage_instructions' => mb_substr($usage, 0, 3000),
            'description' => mb_substr($description, 0, 5000),
            'category_id' => $categoryId,
            'price' => $this->money($decoded['price'] ?? null),
            'stock' => $this->stock($decoded['stock'] ?? null),
            'piece_count' => $this->pieces($decoded['piece_count'] ?? null),
            'weight_label' => mb_substr(trim((string) ($decoded['weight_label'] ?? '')), 0, 80),
            'quantity_label' => mb_substr(trim((string) ($decoded['quantity_label'] ?? '')), 0, 120),
        ];
    }

    /**
     * @param  array{
     *     benefits: list<string>,
     *     keywords: list<string>,
     *     usage_instructions: string,
     *     description: string,
     *     category_id: int|null,
     *     price: float|null,
     *     stock: int|null,
     *     piece_count: int|null,
     *     weight_label: string,
     *     quantity_label: string
     * }  $parsed
     * @param  array{weight_label: string, piece_count: int|null, quantity_label: string, search_tokens: list<string>}  $hints
     * @param  array<string, mixed>  $input
     * @return array{
     *     benefits: list<string>,
     *     keywords: list<string>,
     *     usage_instructions: string,
     *     description: string,
     *     category_id: int|null,
     *     price: float|null,
     *     stock: int|null,
     *     piece_count: int|null,
     *     weight_label: string,
     *     quantity_label: string
     * }
     */
    private function merge(array $parsed, array $hints, array $input, ?int $lockedCategoryId): array
    {
        if ($lockedCategoryId) {
            $parsed['category_id'] = $lockedCategoryId;
        }

        if ($parsed['weight_label'] === '' && trim((string) ($input['weight_label'] ?? '')) !== '') {
            $parsed['weight_label'] = trim((string) $input['weight_label']);
        } elseif ($parsed['weight_label'] === '' && $hints['weight_label'] !== '') {
            $parsed['weight_label'] = $hints['weight_label'];
        }

        if ($parsed['piece_count'] === null && is_numeric($input['piece_count'] ?? null)) {
            $parsed['piece_count'] = $this->pieces($input['piece_count']);
        } elseif ($parsed['piece_count'] === null) {
            $parsed['piece_count'] = $hints['piece_count'];
        }

        if ($parsed['quantity_label'] === '' && trim((string) ($input['quantity_label'] ?? '')) !== '') {
            $parsed['quantity_label'] = trim((string) $input['quantity_label']);
        } elseif ($parsed['quantity_label'] === '' && $hints['quantity_label'] !== '') {
            $parsed['quantity_label'] = $hints['quantity_label'];
        }

        if ($parsed['stock'] === null) {
            $parsed['stock'] = random_int(18, 45);
        }

        return $parsed;
    }

    /**
     * @param  array{weight_label: string, piece_count: int|null, quantity_label: string, search_tokens: list<string>}  $hints
     * @param  array<string, mixed>  $input
     */
    private function cacheKey(string $name, array $input, ?int $categoryId, array $hints): string
    {
        return 'ai:product_copy:'.hash('sha256', json_encode([
            'name' => mb_strtolower($name),
            'category_id' => $categoryId,
            'description' => mb_substr(trim((string) ($input['description'] ?? '')), 0, 120),
            'weight' => $hints['weight_label'],
            'pieces' => $hints['piece_count'],
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function resolveCategoryId(mixed $id, mixed $label, Collection $categories): ?int
    {
        if (is_numeric($id)) {
            $match = $categories->firstWhere('id', (int) $id);
            if ($match) {
                return (int) $match->id;
            }
        }

        $needle = trim((string) $label);
        if ($needle === '') {
            return null;
        }

        $match = $categories->first(function (Category $category) use ($needle) {
            $path = (string) ($category->path_label ?? $category->name);

            return $category->name === $needle
                || $path === $needle
                || str_contains($path, $needle);
        });

        return $match ? (int) $match->id : null;
    }

    private function money(mixed $value): ?float
    {
        if (is_string($value)) {
            $value = str_replace(['ر.س', 'ريال', ',', ' '], '', $value);
        }
        if (! is_numeric($value)) {
            return null;
        }

        $amount = round((float) $value, 2);

        return $amount > 0 && $amount <= 99999 ? $amount : null;
    }

    private function stock(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $stock = (int) $value;

        return $stock >= 0 && $stock <= 999999 ? $stock : null;
    }

    private function pieces(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $count = (int) $value;

        return $count >= 2 && $count <= 9999 ? $count : null;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\r\n|\r|\n|,|،/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            $text = trim((string) $item);
            $text = trim($text, " \t\n\r\0\x0B-•");
            if ($text !== '') {
                $items[] = $text;
            }
        }

        return array_values(array_unique($items));
    }
}
