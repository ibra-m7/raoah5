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

    public function __construct(
        private readonly GeminiClient $gemini,
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
     *     quantity_label: string
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

        $categories = $this->categoryOptionsFor($input['category_id'] ?? null);
        $system = $this->systemPrompt($categories);

        try {
            $raw = $fast
                ? $this->gemini->generateJsonFast($system, $this->userPrompt($name, $input, $categories))
                : $this->gemini->generateJson($system, $this->userPrompt($name, $input, $categories));
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage() !== '' ? $e->getMessage() : 'gemini_failed', 0, $e);
        }

        return $this->parse($raw, $name, $categories);
    }

    /**
     * @param  array{name?: string, category_id?: int|string|null, description?: string|null, weight_label?: string|null, quantity_label?: string|null, piece_count?: int|string|null}  $input
     * @param  Collection<int, Category>  $categories
     */
    private function userPrompt(string $name, array $input, Collection $categories): string
    {
        $lines = ['اسم المنتج: '.$name];

        $category = $this->categoryLabel($input['category_id'] ?? null, $categories);
        if ($category !== '') {
            $lines[] = 'التصنيف الحالي: '.$category;
        }

        $description = trim((string) ($input['description'] ?? ''));
        if ($description !== '') {
            $lines[] = 'الوصف الحالي: '.$description;
        }

        $weight = trim((string) ($input['weight_label'] ?? ''));
        if ($weight !== '') {
            $lines[] = 'الوزن: '.$weight;
        }

        $quantity = trim((string) ($input['quantity_label'] ?? ''));
        if ($quantity !== '') {
            $lines[] = 'الكمية: '.$quantity;
        }

        $pieces = (int) ($input['piece_count'] ?? 0);
        if ($pieces > 1) {
            $lines[] = 'عدد الحبات في العبوة: '.$pieces;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function categoryLabel(mixed $categoryId, Collection $categories): string
    {
        if (! is_numeric($categoryId)) {
            return '';
        }

        $category = $categories->firstWhere('id', (int) $categoryId);
        if ($category) {
            return (string) ($category->path_label ?? $category->name);
        }

        $fallback = Category::query()->with('parent')->find((int) $categoryId);
        if (! $fallback) {
            return '';
        }

        return trim(($fallback->parent?->name ? $fallback->parent->name.' / ' : '').$fallback->name);
    }

    /**
     * @return Collection<int, Category>
     */
    private function categoryOptionsFor(mixed $categoryId): Collection
    {
        $all = $this->categoryOptions();

        if (! is_numeric($categoryId)) {
            return $all;
        }

        $current = $all->firstWhere('id', (int) $categoryId);
        if (! $current) {
            return $all;
        }

        $parentId = (int) ($current->parent_id ?? 0);
        $filtered = $all->filter(function (Category $category) use ($current, $parentId) {
            if ((int) $category->id === (int) $current->id) {
                return true;
            }

            if ($parentId > 0 && (int) ($category->parent_id ?? 0) === $parentId) {
                return true;
            }

            return (int) ($category->depth ?? 0) >= 2;
        });

        return $filtered->count() >= 8 ? $filtered->values() : $all;
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
        $categoryJson = $categories
            ->map(fn (Category $category) => [
                'id' => (int) $category->id,
                'path' => (string) ($category->path_label ?? $category->name),
            ])
            ->values()
            ->toJson(JSON_UNESCAPED_UNICODE);

        return <<<TXT
أنت مساعد إضافة منتجات لمتجر بقالة سعودي اسمه «روعة الخمسة».
أرجع JSON فقط بهذا الشكل:
{"description":"...","category_id":123,"price":12.5,"stock":20,"piece_count":null,"weight_label":"","quantity_label":"","benefits":["..."],"keywords":["..."],"usage_instructions":"..."}
- description: وصف عربي قصير من سطرين إلى ثلاثة، بدون مبالغة.
- category_id: رقم تصنيف من القائمة فقط. اختر أدق تصنيف فرعي.
- price: سعر تجزئة تقريبي بالريال السعودي للمنتج في السوق السعودي، رقم فقط بدون عملة.
- stock: كمية مخزون ابتدائية معقولة بين 10 و 80.
- piece_count: عدد الحبات إن وُجد في الاسم، وإلا null.
- weight_label: الوزن أو الحجم الظاهر مثل «500 مل» أو «1 كجم»، وإلا نص فارغ.
- quantity_label: وصف كمية مختصر إن لزم، وإلا نص فارغ.
- benefits: من 3 إلى 5 فوائد قصيرة، جملة واحدة لكل فائدة.
- keywords: من 6 إلى 12 كلمة أو عبارة بحث عربية شائعة.
- usage_instructions: طريقة استخدام عملية من سطرين إلى أربعة أسطر.
اكتب بأسلوب بسيط يناسب العميل، ولا تختلق ادعاءات طبية أو ضمانات مبالغ فيها.
التصنيفات المتاحة (JSON): {$categoryJson}
TXT;
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
    private function parse(string $raw, string $name, Collection $categories): array
    {
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            if (preg_match('/\{.*\}/s', $raw, $match) === 1) {
                $decoded = json_decode($match[0], true);
            }
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
            $benefits = ['منتج مناسب للاستخدام اليومي'];
        }
        if ($keywords === []) {
            $keywords = [$name];
        }
        if ($usage === '') {
            $usage = 'يُستخدم حسب الحاجة اليومية.';
        }

        return [
            'benefits' => array_slice($benefits, 0, 6),
            'keywords' => array_slice($keywords, 0, 12),
            'usage_instructions' => mb_substr($usage, 0, 3000),
            'description' => mb_substr($description, 0, 5000),
            'category_id' => $this->resolveCategoryId($decoded['category_id'] ?? null, $decoded['category'] ?? null, $categories),
            'price' => $this->money($decoded['price'] ?? null),
            'stock' => $this->stock($decoded['stock'] ?? null),
            'piece_count' => $this->pieces($decoded['piece_count'] ?? null),
            'weight_label' => mb_substr(trim((string) ($decoded['weight_label'] ?? '')), 0, 80),
            'quantity_label' => mb_substr(trim((string) ($decoded['quantity_label'] ?? '')), 0, 120),
        ];
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
