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

    private const GROCERIES_ROOT = 'المقاضي';

    /** @var array<string, Category> */
    private array $categoryCache = [];

    public function __construct(
        private readonly ProductService $products,
        private readonly CategoryService $categories,
    ) {}

    /**
     * @return list<array{key: string, header: string, required: bool, hint: string, example: string}>
     */
    public static function columns(): array
    {
        return [
            ['key' => 'name', 'header' => 'الاسم', 'required' => true, 'hint' => 'اسم المنتج كما سيظهر في التطبيق', 'example' => 'سماعات بلوتوث لاسلكية'],
            ['key' => 'category', 'header' => 'القسم', 'required' => true, 'hint' => 'اسم القسم يكفي — يُنشأ تلقائياً تحت «المقاضي» إن لم يكن موجوداً. يمكنك كتابة مسار مثل: المقاضي > بسكويت', 'example' => 'بسكويت'],
            ['key' => 'price', 'header' => 'السعر', 'required' => true, 'hint' => 'السعر الأصلي بالريال، أرقام فقط', 'example' => '10'],
            ['key' => 'stock', 'header' => 'المخزون', 'required' => true, 'hint' => 'الكمية المتوفرة', 'example' => '50'],
            ['key' => 'barcode', 'header' => 'الباركود', 'required' => false, 'hint' => 'رقم يطابق اسم الصورة في ZIP مثل 3.png للمنتج ذي الباركود 3', 'example' => '1'],
            ['key' => 'sku', 'header' => 'رمز المنتج', 'required' => false, 'hint' => 'اختياري. إن وُجد منتج بنفس الرمز يُحدَّث بدل إنشائه', 'example' => 'SKU-001'],
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

        $imageCount = $this->templateImageCount();
        $productRows = [$header];
        for ($n = 1; $n <= $imageCount; $n++) {
            $row = [];
            foreach ($columns as $col) {
                $row[] = ['value' => $this->templateCell($col['key'], $n)];
            }
            $productRows[] = $row;
        }

        $guide = [
            [['value' => 'كيف تستخدم القالب', 'style' => 'Title']],
            [['value' => '1) لا تحذف صف العناوين الأول ولا تغيّر أسماء الأعمدة.']],
            [['value' => '2) الأعمدة الحمراء إلزامية في كل صف. الخضراء اختيارية.']],
            [['value' => '3) القالب يحتوي '.$imageCount.' منتجاً بقالة مطابقاً لصور public/img. الباركود من 1 إلى '.$imageCount.' — عدّل الأسماء والأسعار حسب متجرك.']],
            [['value' => '4) سمِّ صور المنتجات برقم الباركود مثل 3.png أو 3.jpg وضعها في ملف ZIP.']],
            [['value' => '5) ارفع ملف Excel مع ملف ZIP من صفحة الاستيراد لربط كل صورة بمنتجها تلقائياً.']],
            [['value' => '6) عمود القسم مملوء مسبقاً — إن كتبت قسماً جديداً يُنشأ تلقائياً تحت «المقاضي» دون الحاجة لنسخه من ورقة الأقسام.']],
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
                'Required' => '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:ReadingOrder="RightToLeft" ss:WrapText="1"/><Font ss:Bold="1" ss:Color="#9B1C1C" ss:Size="11"/><Interior ss:Color="#FDECEC" ss:Pattern="Solid"/>',
                'Optional' => '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:ReadingOrder="RightToLeft" ss:WrapText="1"/><Font ss:Bold="1" ss:Color="#166534" ss:Size="11"/><Interior ss:Color="#E8F8EC" ss:Pattern="Solid"/>',
                'Title' => '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:ReadingOrder="RightToLeft" ss:WrapText="1"/><Font ss:Bold="1" ss:Size="14" ss:Color="#166534"/>',
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
        $this->categoryCache = [];

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
                    } else {
                        $this->products->clearPrimaryImageIfMissingLocal($product->fresh());
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
                $originalName = $zip->getNameIndex($i);
                if (! is_string($originalName) || $originalName === '') {
                    continue;
                }
                $name = self::normalizeZipEntryName($originalName);
                if ($name === null) {
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
                $stream = $zip->getStream($originalName);
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

    public static function normalizeZipEntryName(string $name): ?string
    {
        $name = ltrim(str_replace('\\', '/', $name), '/');
        if ($name === '' || str_ends_with($name, '/') || str_contains($name, '..')) {
            return null;
        }

        return $name;
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

        $category = $this->findOrCreateCategory($categoryName);

        $priceNum = (float) str_replace(',', '.', $price);

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
        if ($name === '') {
            return null;
        }

        return Category::query()
            ->get(['id', 'name', 'parent_id'])
            ->first(fn (Category $category) => mb_strtolower(trim($category->name)) === mb_strtolower($name));
    }

    private function findOrCreateCategory(string $name): Category
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('القسم مطلوب.');
        }

        $cacheKey = mb_strtolower($name);
        if (isset($this->categoryCache[$cacheKey])) {
            return $this->categoryCache[$cacheKey];
        }

        if (str_contains($name, '>')) {
            $parts = array_values(array_filter(array_map('trim', preg_split('/\s*>\s*/u', $name) ?: [])));
            if ($parts === []) {
                throw new RuntimeException('مسار القسم غير صالح.');
            }

            $parent = null;
            $leaf = array_pop($parts);
            foreach ($parts as $part) {
                $parent = $this->resolveCategoryNode($part, $parent?->id);
            }

            $category = $this->resolveCategoryNode($leaf, $parent?->id);
            $this->categoryCache[$cacheKey] = $category;

            return $category;
        }

        $existing = $this->findCategory($name);
        if ($existing !== null) {
            $this->categoryCache[$cacheKey] = $existing;

            return $existing;
        }

        $groceriesRoot = $this->findOrCreateGroceriesRoot();
        $category = $this->categories->create([
            'name' => $name,
            'parent_id' => $groceriesRoot->id,
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $this->categoryCache[$cacheKey] = $category;

        return $category;
    }

    private function resolveCategoryNode(string $name, ?int $parentId): Category
    {
        $existing = Category::query()
            ->where('parent_id', $parentId)
            ->get(['id', 'name', 'parent_id'])
            ->first(fn (Category $category) => mb_strtolower(trim($category->name)) === mb_strtolower($name));

        if ($existing !== null) {
            return $existing;
        }

        if ($parentId === null && mb_strtolower($name) === mb_strtolower(self::GROCERIES_ROOT)) {
            return $this->findOrCreateGroceriesRoot();
        }

        if ($parentId === null) {
            $groceriesRoot = $this->findOrCreateGroceriesRoot();

            return $this->categories->create([
                'name' => $name,
                'parent_id' => $groceriesRoot->id,
                'is_active' => true,
                'sort_order' => 0,
            ]);
        }

        return $this->categories->create([
            'name' => $name,
            'parent_id' => $parentId,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function findOrCreateGroceriesRoot(): Category
    {
        $cacheKey = mb_strtolower(self::GROCERIES_ROOT);
        if (isset($this->categoryCache[$cacheKey])) {
            return $this->categoryCache[$cacheKey];
        }

        $existing = Category::query()
            ->whereNull('parent_id')
            ->get(['id', 'name', 'parent_id'])
            ->first(fn (Category $category) => mb_strtolower(trim($category->name)) === mb_strtolower(self::GROCERIES_ROOT));

        if ($existing === null) {
            $existing = Category::query()
                ->get(['id', 'name', 'parent_id'])
                ->first(fn (Category $category) => mb_strtolower(trim($category->name)) === mb_strtolower(self::GROCERIES_ROOT));
        }

        if ($existing !== null) {
            $this->categoryCache[$cacheKey] = $existing;

            return $existing;
        }

        $root = $this->categories->create([
            'name' => self::GROCERIES_ROOT,
            'parent_id' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $this->categoryCache[$cacheKey] = $root;

        return $root;
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

    private function templateCell(string $key, int $n): string
    {
        $product = $this->templateProduct($n);

        return match ($key) {
            'name' => $product['name'],
            'category' => $product['category'],
            'price' => (string) (5 + ($n % 25)),
            'stock' => (string) (10 + ($n % 90)),
            'barcode' => (string) $n,
            'sku' => 'SKU-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
            'is_featured' => 'لا',
            'is_active' => 'نعم',
            'sort_order' => (string) $n,
            default => '',
        };
    }

    public function templateImageCount(): int
    {
        $dir = public_path('img');
        if (! is_dir($dir)) {
            return count($this->templateProducts());
        }

        $count = 0;
        foreach (scandir($dir) ?: [] as $file) {
            if (preg_match('/^\d+\.(?:'.implode('|', self::IMAGE_EXTENSIONS).')$/i', $file) === 1) {
                $count++;
            }
        }

        return $count > 0 ? $count : count($this->templateProducts());
    }

    /**
     * @return array{name: string, category: string}
     */
    private function templateProduct(int $n): array
    {
        $products = $this->templateProducts();
        $index = max(1, $n) - 1;

        return $products[$index] ?? [
            'name' => 'منتج '.$n,
            'category' => 'بسكويت',
        ];
    }

    /**
     * @return list<array{name: string, category: string}>
     */
    private function templateProducts(): array
    {
        return [
            ['name' => 'بسكويت أبو ولد بكريمة الشوكولاتة', 'category' => 'بسكويت'],
            ['name' => 'بسكويت أبو ولد بكريمة الشوكولاتة', 'category' => 'بسكويت'],
            ['name' => 'بسكويت أبو ولد بكريمة الفراولة', 'category' => 'بسكويت'],
            ['name' => 'بسكويت أبو ولد بكريمة الفراولة', 'category' => 'بسكويت'],
            ['name' => 'بسكويت أبو ولد بكريمة الفراولة', 'category' => 'بسكويت'],
            ['name' => 'بسكويت أبو ولد بكريمة الفراولة', 'category' => 'بسكويت'],
            ['name' => 'زيت كريم نباتي للقلي والطبخ', 'category' => 'الزيوت والصلصات'],
            ['name' => 'زيت كريم نباتي للقلي والطبخ', 'category' => 'الزيوت والصلصات'],
            ['name' => 'بسكويت ماري', 'category' => 'بسكويت'],
            ['name' => 'مسحوق غسيل كريستال', 'category' => 'غسيل الملابس'],
            ['name' => 'حلاوة طحينية الفنار', 'category' => 'حلويات'],
            ['name' => 'بسكويت ماري', 'category' => 'بسكويت'],
            ['name' => 'ويفر مغطى بالشوكولاتة', 'category' => 'بسكويت'],
            ['name' => 'فاصوليا حمراء الهناء', 'category' => 'المعكرونة والمعلبات'],
            ['name' => 'زيت القمرية أولين النخيل', 'category' => 'الزيوت والصلصات'],
            ['name' => 'نودلز نوودي بنكهة الدجاج الخاصة', 'category' => 'المعكرونة والمعلبات'],
            ['name' => 'زيت نباتي القمرية 15 كجم', 'category' => 'الزيوت والصلصات'],
            ['name' => 'مسحوق غسيل كريستال برائحة الورد 2.5 كجم', 'category' => 'غسيل الملابس'],
            ['name' => 'مناديل سوفلي 800 منديل', 'category' => 'ورق ومناديل'],
            ['name' => 'ثوم', 'category' => 'الخضروات والفواكه'],
            ['name' => 'سمن نباتي البنت بنكهة الحلبة', 'category' => 'الزيوت والصلصات'],
            ['name' => 'بسكويت ويفر تيشوب بالشوكولاتة', 'category' => 'بسكويت'],
            ['name' => 'سمن نباتي البنت 14 كجم', 'category' => 'الزيوت والصلصات'],
            ['name' => 'حليب مبخر الممتاز', 'category' => 'الحليب والألبان'],
            ['name' => 'زيت نباتي القمرية', 'category' => 'الزيوت والصلصات'],
            ['name' => 'سائل غسيل صحون ليجا بالليمون 500 مل', 'category' => 'منظفات المطبخ'],
            ['name' => 'زيت كريم نباتي نقي', 'category' => 'الزيوت والصلصات'],
            ['name' => 'زيت كريم نباتي', 'category' => 'الزيوت والصلصات'],
            ['name' => 'زيت كريم نباتي', 'category' => 'الزيوت والصلصات'],
            ['name' => 'زيت كريم نباتي للقلي والطبخ', 'category' => 'الزيوت والصلصات'],
            ['name' => 'مسحوق غسيل كريستال', 'category' => 'غسيل الملابس'],
            ['name' => 'مسحوق غسيل كريستال', 'category' => 'غسيل الملابس'],
            ['name' => 'حلاوة طحينية الفنار', 'category' => 'حلويات'],
            ['name' => 'بسكويت ماري', 'category' => 'بسكويت'],
            ['name' => 'ويفر مغطى بالشوكولاتة', 'category' => 'بسكويت'],
            ['name' => 'فاصوليا حمراء الهناء', 'category' => 'المعكرونة والمعلبات'],
            ['name' => 'زيت القمرية أولين النخيل', 'category' => 'الزيوت والصلصات'],
            ['name' => 'نودلز نوودي بنكهة الدجاج الخاصة', 'category' => 'المعكرونة والمعلبات'],
            ['name' => 'نودلز نوودي بنكهة الدجاج الخاصة', 'category' => 'المعكرونة والمعلبات'],
            ['name' => 'زيت نباتي القمرية 15 كجم', 'category' => 'الزيوت والصلصات'],
            ['name' => 'زيت نباتي القمرية 15 كجم', 'category' => 'الزيوت والصلصات'],
            ['name' => 'مسحوق غسيل كريستال برائحة الورد', 'category' => 'غسيل الملابس'],
            ['name' => 'مسحوق غسيل كريستال برائحة الورد 2.5 كجم', 'category' => 'غسيل الملابس'],
            ['name' => 'مناديل سوفلي 800 منديل', 'category' => 'ورق ومناديل'],
            ['name' => 'ثوم', 'category' => 'الخضروات والفواكه'],
            ['name' => 'سمن نباتي البنت بنكهة الزبدة', 'category' => 'الزيوت والصلصات'],
            ['name' => 'سمن نباتي البنت 14 كجم', 'category' => 'الزيوت والصلصات'],
            ['name' => 'حليب مبخر الممتاز', 'category' => 'الحليب والألبان'],
            ['name' => 'زيت نباتي القمرية', 'category' => 'الزيوت والصلصات'],
            ['name' => 'سائل غسيل صحون ليجا بالليمون 500 مل', 'category' => 'منظفات المطبخ'],
            ['name' => 'زيت كريم نباتي', 'category' => 'الزيوت والصلصات'],
            ['name' => 'زيت كريم نباتي', 'category' => 'الزيوت والصلصات'],
            ['name' => 'مسحوق غسيل كريستال', 'category' => 'غسيل الملابس'],
            ['name' => 'نودلز نوودي بنكهة الدجاج الخاصة', 'category' => 'المعكرونة والمعلبات'],
            ['name' => 'زيت نباتي القمرية 15 كجم', 'category' => 'الزيوت والصلصات'],
            ['name' => 'زيت نباتي القمرية 15 كجم', 'category' => 'الزيوت والصلصات'],
            ['name' => 'مسحوق غسيل كريستال برائحة الورد 2.5 كجم', 'category' => 'غسيل الملابس'],
            ['name' => 'زيت نباتي القمرية 1.5 كجم', 'category' => 'الزيوت والصلصات'],
        ];
    }
}
