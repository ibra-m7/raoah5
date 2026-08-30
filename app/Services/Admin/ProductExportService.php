<?php

namespace App\Services\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Support\Excel\SpreadsheetXml;
use App\Support\Media;

class ProductExportService
{
    /** @var array<int, Category> */
    private array $categoriesById = [];

    /**
     * @param  array{q?: string, status?: string, category_id?: int|string|null}  $filters
     */
    public function xml(array $filters = []): string
    {
        $columns = ProductImportService::columns();
        $this->categoriesById = Category::query()->get()->keyBy('id')->all();

        $header = [];
        $widths = [];
        foreach ($columns as $i => $col) {
            $header[] = [
                'value' => $col['header'].($col['required'] ? ' *' : ''),
                'style' => $col['required'] ? 'Required' : 'Optional',
            ];
            $widths[] = $i < 5 ? 120 : 150;
        }

        $rows = [$header];
        foreach ($this->products($filters) as $product) {
            $rows[] = $this->rowFromProduct($product, $columns);
        }

        return SpreadsheetXml::document(
            [
                [
                    'name' => 'المنتجات',
                    'freeze' => true,
                    'widths' => $widths,
                    'rows' => $rows,
                ],
            ],
            [
                'Required' => '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:ReadingOrder="RightToLeft" ss:WrapText="1"/><Font ss:Bold="1" ss:Color="#9B1C1C" ss:Size="11"/><Interior ss:Color="#FDECEC" ss:Pattern="Solid"/>',
                'Optional' => '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:ReadingOrder="RightToLeft" ss:WrapText="1"/><Font ss:Bold="1" ss:Color="#166534" ss:Size="11"/><Interior ss:Color="#E8F8EC" ss:Pattern="Solid"/>',
            ],
        );
    }

    public function filename(): string
    {
        return 'تصدير-المنتجات-'.now()->format('Y-m-d').'.xls';
    }

    /**
     * @param  array{q?: string, status?: string, category_id?: int|string|null}  $filters
     * @return \Illuminate\Support\LazyCollection<int, Product>
     */
    private function products(array $filters)
    {
        return Product::query()
            ->sellable()
            ->with(['category', 'primaryImage'])
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->search($search))
            ->when(is_numeric($filters['category_id'] ?? null), fn ($query) => $query->forCategory($filters['category_id']))
            ->when(($filters['status'] ?? '') === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? '') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->lazy();
    }

    /**
     * @param  list<array{key: string, header: string, required: bool, hint: string, example: string}>  $columns
     * @return list<array{value: string}>
     */
    private function rowFromProduct(Product $product, array $columns): array
    {
        $values = [
            'name' => $product->name,
            'category' => $this->categoryLabel($product->category),
            'price' => $this->decimal($product->price),
            'stock' => (string) $product->stock,
            'barcode' => (string) ($product->barcode ?? ''),
            'sku' => (string) ($product->sku ?? ''),
            'description' => (string) ($product->description ?? ''),
            'image_url' => Media::url($product->primaryImage?->url) ?? '',
            'benefits' => implode('|', $product->benefits ?? []),
            'keywords' => implode(', ', $product->keywords ?? []),
            'usage_instructions' => (string) ($product->usage_instructions ?? ''),
            'is_featured' => $product->is_featured ? 'نعم' : 'لا',
            'is_active' => $product->is_active ? 'نعم' : 'لا',
            'sort_order' => (string) $product->sort_order,
        ];

        return array_map(
            fn (array $col) => ['value' => $values[$col['key']] ?? ''],
            $columns,
        );
    }

    private function categoryLabel(?Category $category): string
    {
        if ($category === null) {
            return '';
        }

        $parts = [];
        $current = $category;
        $guard = 0;

        while ($current !== null && $guard++ < 10) {
            array_unshift($parts, $current->name);
            $current = $current->parent_id
                ? ($this->categoriesById[$current->parent_id] ?? null)
                : null;
        }

        return implode(' > ', $parts);
    }

    private function decimal(mixed $value): string
    {
        $number = (float) $value;

        if (fmod($number, 1.0) === 0.0) {
            return (string) (int) $number;
        }

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }
}
