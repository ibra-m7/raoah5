<?php

namespace App\Services\Ai;

use App\Models\Category;
use App\Support\AiSettings;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProductCopyGenerator
{
    public function __construct(
        private readonly GeminiClient $gemini,
    ) {}

    /**
     * @param  array{name?: string, category_id?: int|string|null, description?: string|null, weight_label?: string|null, quantity_label?: string|null, piece_count?: int|string|null}  $input
     * @return array{benefits: list<string>, keywords: list<string>, usage_instructions: string}
     */
    public function generate(array $input): array
    {
        if (! AiSettings::hasApiKey()) {
            throw new RuntimeException('missing_key');
        }

        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('missing_name');
        }

        try {
            $raw = $this->gemini->generateJson($this->systemPrompt(), $this->userPrompt($name, $input));
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage() !== '' ? $e->getMessage() : 'gemini_failed', 0, $e);
        }

        return $this->parse($raw, $name);
    }

    /**
     * @param  array{name?: string, category_id?: int|string|null, description?: string|null, weight_label?: string|null, quantity_label?: string|null, piece_count?: int|string|null}  $input
     */
    private function userPrompt(string $name, array $input): string
    {
        $lines = ['اسم المنتج: '.$name];

        $category = $this->categoryLabel($input['category_id'] ?? null);
        if ($category !== '') {
            $lines[] = 'التصنيف: '.$category;
        }

        $description = trim((string) ($input['description'] ?? ''));
        if ($description !== '') {
            $lines[] = 'الوصف: '.$description;
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

    private function categoryLabel(mixed $categoryId): string
    {
        if (! is_numeric($categoryId)) {
            return '';
        }

        $category = Category::query()->with('parent')->find((int) $categoryId);
        if (! $category) {
            return '';
        }

        return trim(($category->parent?->name ? $category->parent->name.' / ' : '').$category->name);
    }

    private function systemPrompt(): string
    {
        return <<<'TXT'
أنت كاتب محتوى عربي لمتجر بقالة سعودي اسمه «روعة الخمسة».
أرجع JSON فقط بهذا الشكل:
{"benefits":["..."],"keywords":["..."],"usage_instructions":"..."}
- benefits: من 3 إلى 5 فوائد قصيرة وواضحة، جملة واحدة لكل فائدة.
- keywords: من 6 إلى 12 كلمة أو عبارة بحث عربية شائعة، بدون تكرار، وتشمل الاسم والمرادفات والاستخدام.
- usage_instructions: طريقة استخدام عملية من سطرين إلى أربعة أسطر.
اكتب بأسلوب بسيط يناسب العميل، ولا تختلق ادعاءات طبية أو ضمانات مبالغ فيها.
TXT;
    }

    /**
     * @return array{benefits: list<string>, keywords: list<string>, usage_instructions: string}
     */
    private function parse(string $raw, string $name): array
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
        ];
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
