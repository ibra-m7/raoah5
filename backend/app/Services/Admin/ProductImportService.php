<?php

namespace App\Services\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Support\Excel\SpreadsheetReader;
use App\Support\Excel\SpreadsheetXml;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductImportService
{
    public function __construct(private readonly ProductService $products) {}

    /**
     * @return list<array{key: string, header: string, required: bool, hint: string, example: string}>
     */
    public static function columns(): array
    {
        return [
            ['key' => 'name', 'header' => 'الاسم', 'required' => true, 'hint' => 'اسم المنتج كما سيظهر في التطبيق', 'example' => 'زيت الزيتون البكر 500مل'],
            ['key' => 'category', 'header' => 'القسم', 'required' => true, 'hint' => 'انسخ الاسم حرفياً من ورقة الأقسام', 'example' => 'مواد غذائية'],
            ['key' => 'price', 'header' => 'السعر', 'required' => true, 'hint' => 'السعر الأصلي بالريال، أرقام فقط', 'example' => '45.5'],
            ['key' => 'stock', 'header' => 'المخزون', 'required' => true, 'hint' => 'الكمية المتوفرة', 'example' => '80'],
            ['key' => 'sku', 'header' => 'رمز المنتج', 'required' => false, 'hint' => 'اختياري. إن وُجد منتج بنفس الرمز يُحدَّث بدل إنشائه', 'example' => 'OIL-500'],
            ['key' => 'discount_price', 'header' => 'سعر العرض', 'required' => false, 'hint' => 'أقل من السعر الأصلي ليظهر في العروض', 'example' => '39.9'],
            ['key' => 'description', 'header' => 'الوصف', 'required' => false, 'hint' => 'وصف قصير للمنتج', 'example' => 'زيت زيتون بكر ممتاز من مزارع الجوف'],
            ['key' => 'image_url', 'header' => 'رابط الصورة', 'required' => false, 'hint' => 'رابط مباشر لصورة المنتج (https://...)', 'example' => 'https://example.com/oil.jpg'],
            ['key' => 'benefits', 'header' => 'الفوائد', 'required' => false, 'hint' => 'افصل كل فائدة بـ |', 'example' => 'غني بمضادات الأكسدة | مناسب للسلطات'],
            ['key' => 'keywords', 'header' => 'كلمات البحث', 'required' => false, 'hint' => 'افصل بفاصلة', 'example' => 'زيت, زيتون, عضوي'],
            ['key' => 'usage_instructions', 'header' => 'طريقة الاستخدام', 'required' => false, 'hint' => 'نص حر', 'example' => 'يُستخدم بارداً مع السلطات'],
            ['key' => 'is_featured', 'header' => 'مميز', 'required' => false, 'hint' => 'نعم أو لا', 'example' => 'نعم'],
            ['key' => 'is_active', 'header' => 'ظاهر', 'required' => false, 'hint' => 'نعم أو لا — الافتراضي نعم', 'example' => 'نعم'],
            ['key' => 'sort_order', 'header' => 'الترتيب', 'required' => false, 'hint' => 'رقم أصغر يظهر أولاً', 'example' => '10'],
        ];
    }

    public function templateFilename(): string
    {
        return 'قالب-استيراد-المنتجات.xls';
    }

    public function templateXml(): string
    {
        $columns = self::columns();
        $header = [];
        $example = [];
        $example2 = [];
        $widths = [];
        foreach ($columns as $i => $col) {
            $header[] = [
                'value' => $col['header'].($col['required'] ? ' *' : ''),
                'style' => $col['required'] ? 'Required' : 'Optional',
            ];
            $example[] = ['value' => $col['example']];
            $example2[] = ['value' => $this->secondExample($col['key'])];
            $widths[] = $i < 4 ? 140 : 160;
        }
        $catNames = Category::query()->orderBy('name')->pluck('name');
        if ($catNames->isNotEmpty()) {
            $example[1]['value'] = $catNames[0];
            $example2[1]['value'] = $catNames[1] ?? $catNames[0];
        }

        $guide = [
            [['value' => 'كيف تستخدم القالب', 'style' => 'Title']],
            [['value' => '1) لا تحذف صف العناوين الأول ولا تغيّر أسماء الأعمدة.']],
            [['value' => '2) الأعمدة الحمراء إلزامية في كل صف. الخضراء اختيارية.']],
            [['value' => '3) انسخ اسم القسم من ورقة «الأقسام» كما هو بدون زيادة مسافات.']],
            [['value' => '4) إذا وضعت «رمز المنتج» لمنتج موجود مسبقاً سيتم تحديثه بدل إنشاء صف جديد.']],
            [['value' => '5) احفظ الملف ثم ارفعه من: المنتجات ← استيراد Excel. يمكن أيضاً الحفظ كـ CSV UTF-8.']],
            [['value' => '']],
            [['value' => 'الأعمدة', 'style' => 'Title']],
        ];
        $guide[] = [
            ['value' => 'العمود', 'style' => 'Optional'],
            ['value' => 'إلزامي؟', 'style' => 'Optional'],
            ['value' => 'الشرح', 'style' => 'Optional'],
        ];
        foreach ($columns as $col) {
            $guide[] = [
                ['value' => $col['header'], 'style' => $col['required'] ? 'Required' : 'Optional'],
                ['value' => $col['required'] ? 'نعم' : 'لا'],
                ['value' => $col['hint']],
            ];
        }

        $categories = Category::query()->with('parent')->orderBy('name')->get();
        $catRows = [[
            ['value' => 'اسم القسم (انسخه لعمود القسم)', 'style' => 'Optional'],
            ['value' => 'القسم الأب', 'style' => 'Optional'],
        ]];
        if ($categories->isEmpty()) {
            $catRows[] = [['value' => 'لا توجد أقسام بعد — أضف قسماً من لوحة التحكم أولاً.']];
        } else {
            foreach ($categories as $category) {
                $catRows[] = [
                    ['value' => $category->name],
                    ['value' => $category->parent?->name ?? '—'],
                ];
            }
        }

        return SpreadsheetXml::document(
            [
                [
                    'name' => 'المنتجات',
                    'freeze' => true,
                    'widths' => $widths,
                    'rows' => [$header, $example, $example2],
                ],
                [
                    'name' => 'التعليمات',
                    'widths' => [90, 70, 420],
                    'rows' => $guide,
                ],
                [
                    'name' => 'الأقسام',
                    'freeze' => true,
                    'widths' => [220, 160],
                    'rows' => $catRows,
                ],
            ],
            [
                'Required' => '<Alignment ss:Horizontal="Center" ss:WrapText="1"/><Font ss:Bold="1" ss:Color="#9B1C1C" ss:Size="11"/><Interior ss:Color="#FDECEC" ss:Pattern="Solid"/>',
                'Optional' => '<Alignment ss:Horizontal="Center" ss:WrapText="1"/><Font ss:Bold="1" ss:Color="#166534" ss:Size="11"/><Interior ss:Color="#E8F8EC" ss:Pattern="Solid"/>',
                'Title' => '<Font ss:Bold="1" ss:Size="14" ss:Color="#166534"/>',
            ],
        );
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function import(UploadedFile $file): array
    {
        $rows = SpreadsheetReader::rows($file);
        if ($rows === []) {
            throw new RuntimeException('الملف فارغ.');
        }

        $headerIndex = $this->headerIndex($rows);
        if ($headerIndex === null) {
            throw new RuntimeException('لم يُعثر على صف العناوين. استخدم القالب كما هو دون تغيير أسماء الأعمدة.');
        }

        $map = $this->mapHeaders($rows[$headerIndex]);
        foreach (['name', 'category', 'price', 'stock'] as $required) {
            if (! isset($map[$required])) {
                throw new RuntimeException('العمود الإلزامي «'.$this->headerFor($required).'» غير موجود في الملف.');
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            for ($i = $headerIndex + 1; $i < count($rows); $i++) {
                $line = $i + 1;
                $raw = $rows[$i];
                if ($this->rowEmpty($raw)) {
                    $skipped++;
                    continue;
                }

                try {
                    $payload = $this->payloadFromRow($raw, $map);
                    $existing = $this->existingBySku($payload['sku'] ?? null);
                    if ($existing) {
                        $this->products->update($existing, $payload);
                        $updated++;
                    } else {
                        $this->products->create($payload);
                        $created++;
                    }
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $errors[] = 'الصف '.$line.': '.(collect($e->errors())->flatten()->first() ?: $e->getMessage());
                } catch (\Throwable $e) {
                    $errors[] = 'الصف '.$line.': '.$e->getMessage();
                }
            }

            if ($created === 0 && $updated === 0) {
                DB::rollBack();
                throw new RuntimeException(
                    $errors === []
                        ? 'لا توجد صفوف صالحة للاستيراد.'
                        : implode("\n", array_slice($errors, 0, 8))
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            throw $e;
        }

        return compact('created', 'updated', 'skipped', 'errors');
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function headerIndex(array $rows): ?int
    {
        foreach ($rows as $i => $row) {
            $mapped = $this->mapHeaders($row);
            if (isset($mapped['name'], $mapped['price'])) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $row
     * @return array<string, int>
     */
    private function mapHeaders(array $row): array
    {
        $aliases = [];
        foreach (self::columns() as $col) {
            $aliases[$this->normalizeHeader($col['header'])] = $col['key'];
            $aliases[$this->normalizeHeader($col['header'].'*')] = $col['key'];
            $aliases[$this->normalizeHeader($col['key'])] = $col['key'];
        }
        $aliases[$this->normalizeHeader('category_name')] = 'category';
        $aliases[$this->normalizeHeader('اسم المنتج')] = 'name';
        $aliases[$this->normalizeHeader('القسم')] = 'category';

        $map = [];
        foreach ($row as $index => $cell) {
            $key = $aliases[$this->normalizeHeader((string) $cell)] ?? null;
            if ($key && ! isset($map[$key])) {
                $map[$key] = $index;
            }
        }

        return $map;
    }

    /**
     * @param  list<string>  $row
     * @param  array<string, int>  $map
     * @return array<string, mixed>
     */
    private function payloadFromRow(array $row, array $map): array
    {
        $get = function (string $key) use ($row, $map): string {
            $i = $map[$key] ?? null;
            if ($i === null) {
                return '';
            }

            return trim((string) ($row[$i] ?? ''));
        };

        $name = $get('name');
        $categoryName = $get('category');
        $price = $get('price');
        $stock = $get('stock');

        if ($name === '') {
            throw new RuntimeException('الاسم مطلوب.');
        }
        if ($categoryName === '') {
            throw new RuntimeException('القسم مطلوب.');
        }
        if ($price === '' || ! is_numeric(str_replace(',', '.', $price))) {
            throw new RuntimeException('السعر مطلوب ويجب أن يكون رقماً.');
        }
        if ($stock === '' || ! preg_match('/^-?\d+$/', $stock)) {
            throw new RuntimeException('المخزون مطلوب ويجب أن يكون رقماً صحيحاً.');
        }

        $category = $this->findCategory($categoryName);
        if ($category === null) {
            throw new RuntimeException('القسم «'.$categoryName.'» غير موجود. انسخه من ورقة الأقسام.');
        }

        $priceNum = (float) str_replace(',', '.', $price);
        $discount = $get('discount_price');
        $discountNum = $discount === '' ? null : (float) str_replace(',', '.', $discount);
        if ($discountNum !== null && $discountNum >= $priceNum) {
            throw new RuntimeException('سعر العرض يجب أن يكون أقل من السعر الأصلي.');
        }

        $imageUrl = $get('image_url');
        if ($imageUrl !== '' && ! filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('رابط الصورة غير صالح.');
        }

        $sku = strtoupper($get('sku'));

        return [
            'name' => $name,
            'sku' => $sku !== '' ? $sku : null,
            'category_id' => $category->id,
            'description' => $get('description') ?: null,
            'price' => $priceNum,
            'discount_price' => $discountNum,
            'stock' => (int) $stock,
            'image_url' => $imageUrl !== '' ? $imageUrl : null,
            'benefits' => str_replace('|', "\n", $get('benefits')),
            'keywords' => $get('keywords'),
            'usage_instructions' => $get('usage_instructions') ?: null,
            'is_featured' => $this->toBool($get('is_featured'), false),
            'is_active' => $this->toBool($get('is_active'), true),
            'sort_order' => $get('sort_order') === '' ? 0 : (int) $get('sort_order'),
        ];
    }

    private function existingBySku(?string $sku): ?Product
    {
        $sku = strtoupper(trim((string) $sku));
        if ($sku === '') {
            return null;
        }

        return Product::query()->where('sku', $sku)->first();
    }

    private function findCategory(string $name): ?Category
    {
        $name = trim($name);

        return Category::query()
            ->get(['id', 'name'])
            ->first(fn (Category $category) => mb_strtolower(trim($category->name)) === mb_strtolower($name));
    }

    private function toBool(string $value, bool $default): bool
    {
        if ($value === '') {
            return $default;
        }
        $value = mb_strtolower(trim($value));

        return in_array($value, ['1', 'true', 'yes', 'y', 'نعم', 'اي', 'أجل', 'مميز', 'ظاهر'], true);
    }

    private function normalizeHeader(string $value): string
    {
        $value = trim($value);
        $value = str_replace(['*', '٭'], '', $value);
        $value = preg_replace('/\s+/u', '', $value) ?? $value;

        return mb_strtolower($value);
    }

    /**
     * @param  list<string>  $row
     */
    private function rowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function headerFor(string $key): string
    {
        foreach (self::columns() as $col) {
            if ($col['key'] === $key) {
                return $col['header'];
            }
        }

        return $key;
    }

    private function secondExample(string $key): string
    {
        return match ($key) {
            'name' => 'منظف أرضيات برائحة اللافندر',
            'category' => 'منظفات',
            'price' => '18',
            'stock' => '120',
            'sku' => 'CLN-LAV',
            'discount_price' => '',
            'description' => 'ينظف الأرضيات بلمعان ويدوم أثر العطر',
            'image_url' => '',
            'benefits' => 'رائحة ثابتة | مناسب للسيراميك',
            'keywords' => 'منظف, أرضيات',
            'usage_instructions' => 'أضف غطاءً إلى دلو ماء وامسح الأرض',
            'is_featured' => 'لا',
            'is_active' => 'نعم',
            'sort_order' => '20',
            default => '',
        };
    }
}
