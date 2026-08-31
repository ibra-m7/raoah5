<?php

namespace App\Services\Ai;

use App\Support\AiSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class BundleCopyGenerator
{
    private const COPY_CACHE_TTL = 1800;

    public function __construct(
        private readonly GeminiClient $gemini,
    ) {}

    /**
     * @param  array{name?: string, product_names?: list<string>|string|null, section_title?: string|null}  $input
     * @return array{summary: string, description: string, discount_percent: float|null, meta: array{cached: bool, source: string}}
     */
    public function generate(array $input, bool $fast = true): array
    {
        if (! AiSettings::hasApiKey()) {
            throw new RuntimeException('missing_key');
        }

        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '' || mb_strlen($name) < 3) {
            throw new RuntimeException('missing_name');
        }

        $productNames = $this->productNames($input['product_names'] ?? []);
        $sectionTitle = trim((string) ($input['section_title'] ?? ''));
        $cacheKey = 'ai:bundle_copy:'.hash('sha256', json_encode([
            'name' => mb_strtolower($name),
            'products' => $productNames,
            'section' => mb_strtolower($sectionTitle),
        ], JSON_UNESCAPED_UNICODE));

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            $cached['meta'] = ['cached' => true, 'source' => 'cache'];

            return $cached;
        }

        try {
            $raw = $fast
                ? $this->gemini->generateJsonFast($this->systemPrompt(), $this->userPrompt($name, $productNames, $sectionTitle))
                : $this->gemini->generateJson($this->systemPrompt(), $this->userPrompt($name, $productNames, $sectionTitle));
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage() !== '' ? $e->getMessage() : 'gemini_failed', 0, $e);
        }

        $parsed = $this->parse($raw, $name);
        $parsed['meta'] = ['cached' => false, 'source' => 'ai'];
        Cache::put($cacheKey, $parsed, self::COPY_CACHE_TTL);

        return $parsed;
    }

    private function systemPrompt(): string
    {
        return <<<'TXT'
أنت كاتب تسويقي لمتجر بقالة سعودي اسمه «روعة الخمسة»، متخصص في سلات التوفير.
أرجع JSON فقط بهذا الشكل:
{"summary":"...","description":"...","discount_percent":15}
- summary: جملة عربية قصيرة جذابة (حد أقصى 120 حرفاً) تظهر تحت اسم السلة في التطبيق.
- description: وصف عربي من سطرين إلى ثلاثة يوضح قيمة السلة وما يناسبها بدون مبالغة.
- discount_percent: نسبة خصم مقترحة بين 8 و 30، رقم فقط.
لا تختلق أسماء منتجات غير مذكورة. ركّز على التوفير والعملية اليومية.
TXT;
    }

    /**
     * @param  list<string>  $productNames
     */
    private function userPrompt(string $name, array $productNames, string $sectionTitle): string
    {
        $lines = ['اسم السلة: '.$name];

        if ($sectionTitle !== '') {
            $lines[] = 'قسم العرض في التطبيق: '.$sectionTitle;
        }

        if ($productNames !== []) {
            $lines[] = 'منتجات السلة: '.implode('، ', array_slice($productNames, 0, 12));
        } else {
            $lines[] = 'منتجات السلة: لم تُحدد بعد — اقترح ملخصاً ووصفاً عامّين مناسبين للاسم.';
        }

        return implode("\n", $lines);
    }

    /**
     * @return array{summary: string, description: string, discount_percent: float|null}
     */
    private function parse(string $raw, string $name): array
    {
        $decoded = json_decode($raw, true);
        if (! is_array($decoded) && preg_match('/\{.*\}/s', $raw, $match) === 1) {
            $decoded = json_decode($match[0], true);
        }
        if (! is_array($decoded)) {
            Log::warning('gemini.bundle_copy.invalid_json', [
                'snippet' => mb_substr($raw, 0, 180),
            ]);
            throw new RuntimeException('invalid_json');
        }

        $summary = trim((string) ($decoded['summary'] ?? ''));
        $description = trim((string) ($decoded['description'] ?? ''));

        if ($summary === '') {
            $summary = 'سلة توفير مختارة بعناية لطلباتك اليومية.';
        }
        if ($description === '') {
            $description = $name.' تجمّع منتجات أساسية بسعر أوفر للاستخدام اليومي من متجر روعة الخمسة.';
        }

        return [
            'summary' => mb_substr($summary, 0, 500),
            'description' => mb_substr($description, 0, 5000),
            'discount_percent' => $this->discount($decoded['discount_percent'] ?? null),
        ];
    }

    private function discount(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $amount = round((float) $value, 2);

        return $amount >= 5 && $amount <= 40 ? $amount : null;
    }

    /**
     * @param  list<string>|string|mixed  $value
     * @return list<string>
     */
    private function productNames(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\r\n|\r|\n|,|،/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        $names = [];
        foreach ($value as $item) {
            $text = trim((string) $item);
            if ($text !== '') {
                $names[] = mb_substr($text, 0, 120);
            }
        }

        return array_values(array_unique(array_slice($names, 0, 20)));
    }
}
