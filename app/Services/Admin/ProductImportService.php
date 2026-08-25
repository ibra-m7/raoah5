<?php

namespace App\Services\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Support\Excel\SpreadsheetReader;
use App\Support\Excel\SpreadsheetXml;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;
use ZipArchive;

class ProductImportService
{
    private const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp', 'gif'];

    public function __construct(private readonly ProductService $products) {}

    /**
     * @return list<array{key: string, header: string, required: bool, hint: string, example: string}>
     */
    public static function columns(): array
    {
        return [
            ['key' => 'name', 'header' => 'الاسم', 'required' => true, 'hint' => 'اسم المنتج كما سيظهر في التطبيق', 'example' => 'سماعات بلوتوث لاسلكية'],
            ['key' => 'category', 'header' => 'القسم', 'required' => true, 'hint' => 'انسخ الاسم حرفياً من ورقة الأقسام', 'example' => 'مواد غذائية'],
            ['key' => 'price', 'header' => 'السعر', 'required' => true, 'hint' => 'السعر الأصلي بالريال، أرقام فقط', 'example' => '10'],
            ['key' => 'stock', 'header' => 'المخزون', 'required' => true, 'hint' => 'الكمية المتوفرة', 'example' => '50'],
            ['key' => 'barcode', 'header' => 'الباركود', 'required' => false, 'hint' => 'رقم يطابق اسم الصورة في ZIP مثل 3.png للمنتج ذي الباركود 3', 'example' => '1'],
            ['key' => 'sku', 'header' => 'رمز المنتج', 'required' => false, 'hint' => 'اختياري. إن وُجد منتج بنفس الرمز يُحدَّث بدل إنشائه', 'example' => 'SKU-001'],
            ['key' => 'discount_price', 'header' => 'سعر العرض', 'required' => false, 'hint' => 'أقل من السعر الأصلي ليظهر في العروض', 'example' => ''],
            ['key' => 'description', 'header' => 'الوصف', 'required' => false, 'hint' => 'وصف قصير للمنتج', 'example' => ''],
            ['key' => 'image_url', 'header' => 'رابط الصورة', 'required' => false, 'hint' => 'رابط مباشر إن لم تستخدم ZIP', 'example' => ''],
            ['key' => 'benefits', 'header' => 'الفوائد', 'required' => false, 'hint' => 'افصل كل فائدة بـ |', 'example' => ''],
            ['key' => 'keywords', 'header' => 'كلمات البحث', 'required' => false, 'hint' => 'افصل بفاصلة', 'example' => ''],
            ['key' => 'usage_instructions', 'header' => 'طريقة الاستخدام', 'required' => false, 'hint' => 'نص حر', 'example' => ''],
            ['key' => 'is_featured', 'header' => 'مميز', 'required' => false, 'hint' => 'نعم أو لا', 'example' => 'لا'],
            ['key' => 'is_active', 'header' => 'ظاهر', 'required' => false, 'hint' => 'نعم أو لا — الافتراضي نعم', 'example' => 'نعم'],
            ['key' => 'sort_order', 'header' => 'الترتيب', 'required' => false, 'hint' => 'رقم أصغر يظهر أولاً', 'example' => '1'],
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
        $widths = [];
        foreach ($columns as $i => $col) {
            $header[] = [
                'value' => $col['header'].($col['required'] ? ' *' : ''),
                'style' => $col['required'] ? 'Required' : 'Optional',
            ];
            $widths[] = $i < 5 ? 120 : 150;
        }

        $defaultCategory = Category::query()->orderBy('name')->value('name') ?: 'مواد غذائية';
        $productRows = [$header];
        for ($n = 1; $n <= 50; $n++) {
            $row = [];
            foreach ($columns as $col) {
                $row[] = ['value' => $this->templateCell($col['key'], $n, $defaultCategory)];
            }
            $productRows[] = $row;
        }

        $guide = [
            [['value' => 'كيف تستخدم القالب', 'style' => 'Title']],
            [['value' => '1) لا تحذف صف العناوين الأول ولا تغيّر أسماء الأعمدة.']],
            [['value' => '2) الأعمدة الحمراء إلزامية في كل صف. الخضراء اختيارية.']],
            [['value' => '3) القالب يحتوي 50 منتجاً بأسماء حقيقية (إلكترونيات وإكسسوارات). الباركود من 1 إلى 50 — عدّل الأسماء والأسعار حسب متجرك.']],
            [['value' => '4) سمِّ صور المنتجات برقم الباركود مثل 3.png أو 3.jpg وضعها في ملف ZIP.']],
            [['value' => '5) ارفع ملف Excel مع ملف ZIP من صفحة الاستيراد لربط كل صورة بمنتجها تلقائياً.']],
            [['value' => '6) انسخ اسم القسم من ورقة «الأقسام» كما هو بدون زيادة مسافات.']],
            [['value' => '7) إذا وضعت «رمز المنتج» أو «الباركود» لمنتج موجود مسبقاً سيتم تحديثه بدل إنشاء صف جديد.']],
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
                    'rows' => $productRows,
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
     * @return array{created: int, updated: int, skipped: int, images: int, errors: list<string>}
     */
    public function import(UploadedFile $file, ?UploadedFile $imagesZip = null): array
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

        $imageMap = [];
        $extractDir = null;
        if ($imagesZip !== null) {
            [$imageMap, $extractDir] = $this->extractImageZip($imagesZip);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $images = 0;
        $errors = [];
        $usedBarcodes = [];

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
                    $barcodeKey = $this->normalizeBarcodeKey((string) ($payload['barcode'] ?? ''));
                    $existing = $this->existingBySku($payload['sku'] ?? null)
                        ?? $this->existingByBarcode($payload['barcode'] ?? null);

                    if ($existing) {
                        $this->assertBarcodeAvailable($payload['barcode'] ?? null, $existing->id);
                        $product = $this->products->update($existing, $payload);
                        $updated++;
                    } else {
                        $this->assertBarcodeAvailable($payload['barcode'] ?? null, null);
                        $product = $this->products->create($payload);
                        $created++;
                    }

                    if ($barcodeKey !== '' && isset($imageMap[$barcodeKey])) {
                        $this->products->attachPrimaryImageFromPath($product, $imageMap[$barcodeKey]);
                        $usedBarcodes[$barcodeKey] = true;
                        $images++;
                    }
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $errors[] = 'الصف '.$line.': '.(collect($e->errors())->flatten()->first() ?: $e->getMessage());
                } catch (Throwable $e) {
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
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            throw $e;
        } finally {
            if ($extractDir !== null) {
                File::deleteDirectory($extractDir);
            }
        }

        foreach ($imageMap as $barcode => $_path) {
            if (! isset($usedBarcodes[$barcode])) {
                $errors[] = 'صورة ZIP بلا منتج مطابق للباركود: '.$barcode;
            }
        }

        return compact('created', 'updated', 'skipped', 'images', 'errors');
    }

    /**
     * @return array{0: array<string, string>, 1: string}
     */
    private function extractImageZip(UploadedFile $zipFile): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('امتداد ZipArchive غير مفعّل على الخادم.');
        }

        $extractDir = storage_path('app/tmp/product-import-'.uniqid('', true));
        File::ensureDirectoryExists($extractDir);

        $zip = new ZipArchive;
        $opened = $zip->open($zipFile->getRealPath());
        if ($opened !== true) {
            File::deleteDirectory($extractDir);
            throw new RuntimeException('تعذّر فتح ملف ZIP.');
        }

        $map = [];
        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (! is_string($name) || $name === '' || str_ends_with($name, '/')) {
                    continue;
                }
                if (str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, '\\')) {
                    continue;
                }
                $base = basename($name);
                if ($base === '' || str_starts_with($base, '.') || str_contains($name, '__MACOSX')) {
                    continue;
                }

                $extension = strtolower(pathinfo($base, PATHINFO_EXTENSION));
                if (! in_array($extension, self::IMAGE_EXTENSIONS, true)) {
                    continue;
                }

                $stem = pathinfo($base, PATHINFO_FILENAME);
                $barcodeKey = $this->normalizeBarcodeKey($stem);
                if ($barcodeKey === '') {
                    continue;
                }

                $target = $extractDir.DIRECTORY_SEPARATOR.$barcodeKey.'.'.$extension;
                $stream = $zip->getStream($name);
                if ($stream === false) {
                    continue;
                }
                $contents = stream_get_contents($stream);
                fclose($stream);
                if ($contents === false || $contents === '') {
                    continue;
                }
                if (file_put_contents($target, $contents) === false) {
                    continue;
                }
                $map[$barcodeKey] = $target;
            }
        } finally {
            $zip->close();
        }

        if ($map === []) {
            File::deleteDirectory($extractDir);
            throw new RuntimeException('ملف ZIP لا يحتوي صوراً بأسماء باركود مثل 3.png');
        }

        return [$map, $extractDir];
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
        $aliases[$this->normalizeHeader('باركود')] = 'barcode';
        $aliases[$this->normalizeHeader('الباركود')] = 'barcode';

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
        if ($discountNum !== null && $discountNum <= 0) {
            $discountNum = null;
        }
        if ($discountNum !== null && $discountNum >= $priceNum) {
            throw new RuntimeException('سعر العرض يجب أن يكون أقل من السعر الأصلي.');
        }

        $imageUrl = $get('image_url');
        if ($imageUrl !== '' && ! filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('رابط الصورة غير صالح.');
        }

        $sku = strtoupper($get('sku'));
        $barcode = $this->normalizeBarcodeValue($get('barcode'));

        return [
            'name' => $name,
            'sku' => $sku !== '' ? $sku : null,
            'barcode' => $barcode,
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
            'skip_ai_copy' => true,
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

    private function existingByBarcode(?string $barcode): ?Product
    {
        $barcode = $this->normalizeBarcodeValue((string) $barcode);
        if ($barcode === null) {
            return null;
        }

        return Product::query()->where('barcode', $barcode)->first();
    }

    private function assertBarcodeAvailable(?string $barcode, ?int $ignoreId): void
    {
        $barcode = $this->normalizeBarcodeValue((string) $barcode);
        if ($barcode === null) {
            return;
        }

        $exists = Product::query()
            ->where('barcode', $barcode)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw new RuntimeException('الباركود «'.$barcode.'» مستخدم لمنتج آخر.');
        }
    }

    private function normalizeBarcodeValue(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d+$/', $value) === 1) {
            return (string) (int) $value;
        }

        return $value;
    }

    private function normalizeBarcodeKey(string $value): string
    {
        return (string) ($this->normalizeBarcodeValue($value) ?? '');
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

    private function templateCell(string $key, int $n, string $defaultCategory): string
    {
        return match ($key) {
            'name' => $this->templateProductName($n),
            'category' => $defaultCategory,
            'price' => (string) (10 + ($n % 40)),
            'stock' => (string) (20 + ($n % 80)),
            'barcode' => (string) $n,
            'sku' => 'SKU-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
            'is_featured' => 'لا',
            'is_active' => 'نعم',
            'sort_order' => (string) $n,
            default => '',
        };
    }

    /**
     * @return list<string>
     */
    private function templateProductNames(): array
    {
        return [
            'سماعات بلوتوث لاسلكية',
            'شاحن سريع USB-C 65 واط',
            'كابل شحن Type-C بطول 2 متر',
            'باور بانك 20000 مللي أمبير',
            'حافظة سيليكون لهاتف آيفون 15',
            'واقي شاشة زجاجي مقوّى',
            'حامل هاتف مغناطيسي للسيارة',
            'ساعة ذكية رياضية',
            'ماوس لاسلكي صامت',
            'لوحة مفاتيح بلوتوث قابلة للطي',
            'فلاش ميموري USB 64 جيجا',
            'كارت ذاكرة microSD 128 جيجا',
            'محول HDMI إلى VGA',
            'سماعة رأس للألعاب مع مايك',
            'مكبر صوت بلوتوث محمول',
            'كاميرا ويب Full HD',
            'إضاءة LED للمكتب قابلة للتعديل',
            'مروحة تبريد للابتوب',
            'حقيبة لابتوب مقاومة للماء 15.6 إنش',
            'قاعدة تبريد للابتوب بمروحتين',
            'قلم ستايلس للتابلت',
            'حافظة آيباد مع حامل',
            'نظارة واقع افتراضي VR',
            'ريموت تحكم للتلفاز الذكي',
            'محول طاقة متعدد المنافذ',
            'شاحن سيارة مزدوج USB',
            'كابل Lightning أصلي الطول 1 متر',
            'سماعة أذن سلكية مع مايك',
            'مايكروفون Condenser للكمبيوتر',
            'ذراع تحكم لاسلكي للألعاب',
            'قاعدة شحن لاسلكي 15 واط',
            'حلقة MagSafe مغناطيسية',
            'غطاء حماية لكاميرا الهاتف',
            'حامل تابلت مكتبي قابل للطي',
            'محول OTG من USB-C إلى USB',
            'كابل شبكة Ethernet Cat6 بطول 5 متر',
            'راوتر لاسلكي ثنائي النطاق',
            'موسع مدى واي فاي',
            'طابعة صور محمولة',
            'ماسح ضوئي محمول للمستندات',
            'قرص صلب خارجي 1 تيرابايت',
            'SSD خارجي 500 جيجا',
            'مشغل MP3 مع سماعات',
            'راديو بلوتوث مع ساعة منبّه',
            'مصباح ليزر للمؤتمرات',
            'قلم ليزر أحمر للعرض',
            'حقيبة منظمة لكابلات الشحن',
            'مجموعة مفكات للإلكترونيات',
            'منظف شاشات مع قماش ميكروفايبر',
            'شريط LED RGB بطول 5 متر مع جهاز تحكم',
        ];
    }

    private function templateProductName(int $n): string
    {
        $names = $this->templateProductNames();
        $index = max(1, $n) - 1;

        return $names[$index % count($names)];
    }
}
