<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use App\Services\Admin\ProductImportService;
use App\Support\Excel\SpreadsheetXml;

$sourcePath = $argv[1] ?? '';
$outputPath = $argv[2] ?? (__DIR__.'/../storage/app/منتجات-جاهزة-للاستيراد.xls');

if ($sourcePath === '') {
    fwrite(STDERR, "Usage: php convert_products_to_template.php <source.xls|source.json> [output.xls]\n");
    exit(1);
}

$rows = loadSourceRows($sourcePath);
if ($rows === []) {
    fwrite(STDERR, "Source file is empty.\n");
    exit(1);
}

$header = array_shift($rows);
$index = mapSourceHeaders($header);
foreach (['name', 'category', 'price', 'stock'] as $required) {
    if (! isset($index[$required])) {
        fwrite(STDERR, "Missing required column: {$required}\n");
        exit(1);
    }
}

$columns = ProductImportService::columns();
$headerRow = [];
$widths = [];
foreach ($columns as $i => $col) {
    $headerRow[] = [
        'value' => $col['header'].($col['required'] ? ' *' : ''),
        'style' => $col['required'] ? 'Required' : 'Optional',
    ];
    $widths[] = $i < 5 ? 120 : 150;
}

$productRows = [$headerRow];
$sort = 1;
foreach ($rows as $raw) {
    if (rowEmpty($raw)) {
        continue;
    }

    $get = static function (string $key) use ($raw, $index): string {
        $i = $index[$key] ?? null;
        if ($i === null) {
            return '';
        }

        return trim(cellString($raw[$i] ?? ''));
    };

    $name = $get('name');
    if ($name === '') {
        continue;
    }

    $barcode = $get('barcode');
    $row = [];
    foreach ($columns as $col) {
        $row[] = ['value' => templateValue($col['key'], $get, $barcode, $sort)];
    }
    $productRows[] = $row;
    $sort++;
}

$xml = SpreadsheetXml::document(
    [
        [
            'name' => 'المنتجات',
            'freeze' => true,
            'widths' => $widths,
            'rows' => $productRows,
        ],
    ],
    [
        'Required' => '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:ReadingOrder="RightToLeft" ss:WrapText="1"/><Font ss:Bold="1" ss:Color="#9B1C1C" ss:Size="11"/><Interior ss:Color="#FDECEC" ss:Pattern="Solid"/>',
        'Optional' => '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:ReadingOrder="RightToLeft" ss:WrapText="1"/><Font ss:Bold="1" ss:Color="#166534" ss:Size="11"/><Interior ss:Color="#E8F8EC" ss:Pattern="Solid"/>',
    ],
);

$outputDir = dirname($outputPath);
if (! is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

file_put_contents($outputPath, $xml);

echo 'Wrote '.count($productRows) - 1 .' products to: '.$outputPath.PHP_EOL;

/**
 * @return list<list<mixed>>
 */
function loadSourceRows(string $path): array
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    if ($ext === 'json') {
        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    if ($ext === 'xls') {
        $jsonPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'products_xls_'.bin2hex(random_bytes(4)).'.json';
        $script = __DIR__.DIRECTORY_SEPARATOR.'read_binary_xls.py';
        $cmd = sprintf(
            'python "%s" "%s" "%s"',
            $script,
            str_replace('"', '\"', $path),
            str_replace('"', '\"', $jsonPath),
        );
        exec($cmd, $output, $code);
        if ($code !== 0 || ! is_file($jsonPath)) {
            fwrite(STDERR, implode(PHP_EOL, $output).PHP_EOL);
            fwrite(STDERR, "Failed to read binary .xls. Install Python xlrd: pip install xlrd\n");

            return [];
        }
        $decoded = json_decode((string) file_get_contents($jsonPath), true);
        @unlink($jsonPath);

        return is_array($decoded) ? $decoded : [];
    }

    fwrite(STDERR, "Unsupported source format: {$ext}\n");

    return [];
}

/**
 * @param  list<mixed>  $header
 * @return array<string, int>
 */
function mapSourceHeaders(array $header): array
{
    $aliases = [
        'الباركود' => 'barcode',
        'باركود' => 'barcode',
        'barcode' => 'barcode',
        'اسمالمنتج' => 'name',
        'الاسم' => 'name',
        'name' => 'name',
        'القسم' => 'category',
        'category' => 'category',
        'السعر' => 'price',
        'price' => 'price',
        'المخزون' => 'stock',
        'stock' => 'stock',
        'رمزالمنتج' => 'sku',
        'sku' => 'sku',
    ];

    $map = [];
    foreach ($header as $index => $cell) {
        $key = $aliases[normalizeHeader((string) $cell)] ?? null;
        if ($key !== null && ! isset($map[$key])) {
            $map[$key] = $index;
        }
    }

    return $map;
}

function normalizeHeader(string $value): string
{
    $value = trim($value);
    $value = str_replace(['*', '٭'], '', $value);
    $value = preg_replace('/\s+/u', '', $value) ?? $value;

    return mb_strtolower($value);
}

/**
 * @param  list<mixed>  $row
 */
function rowEmpty(array $row): bool
{
    foreach ($row as $cell) {
        if (trim(cellString($cell)) !== '') {
            return false;
        }
    }

    return true;
}

function cellString(mixed $value): string
{
    if (is_float($value) && floor($value) == $value) {
        return (string) (int) $value;
    }

    return trim((string) $value);
}

/**
 * @param  callable(string): string  $get
 */
function templateValue(string $key, callable $get, string $barcode, int $sort): string
{
    return match ($key) {
        'name' => $get('name'),
        'category' => normalizeCategory($get('category')),
        'price' => formatNumber($get('price')),
        'stock' => formatInteger($get('stock')),
        'barcode' => $barcode !== '' ? $barcode : (string) $sort,
        'sku' => $get('sku'),
        'is_featured' => 'لا',
        'is_active' => 'نعم',
        'sort_order' => (string) $sort,
        default => '',
    };
}

function normalizeCategory(string $value): string
{
    $value = trim($value);
    $value = str_replace(['←', '->', '→'], '>', $value);
    $value = preg_replace('/\s*>\s*/u', ' > ', $value) ?? $value;
    $value = str_replace('الفواكة', 'الفواكه', $value);

    return trim($value);
}

function formatNumber(string $value): string
{
    $value = str_replace(',', '.', trim($value));
    if ($value === '' || ! is_numeric($value)) {
        return $value;
    }
    $num = (float) $value;

    return fmod($num, 1.0) === 0.0 ? (string) (int) $num : rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.');
}

function formatInteger(string $value): string
{
    $value = str_replace(',', '.', trim($value));
    if ($value === '' || ! is_numeric($value)) {
        return $value;
    }

    return (string) (int) round((float) $value);
}
