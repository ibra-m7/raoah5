<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Support\Constants;
use App\Support\Media;
use App\Support\Slug;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'primaryImage'])
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->search($search))
            ->when(is_numeric($filters['category_id'] ?? null), fn ($query) => $query->forCategory($filters['category_id']))
            ->when(($filters['status'] ?? '') === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? '') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();
    }

    public function categoryOptions(): Collection
    {
        return app(CategoryService::class)->indentedOptions();
    }

    public function productFormCategoryOptions(?int $currentId = null): Collection
    {
        return app(CategoryService::class)->productCategoryOptions($currentId);
    }

    public function create(array $data): Product
    {
        $image = $data['image'] ?? null;
        $imageUrl = trim((string) ($data['image_url'] ?? ''));
        unset($data['image'], $data['image_url']);

        $data['sku'] = $this->sku($data['sku'] ?? null);
        $data['slug'] = Slug::unique($data['name'], 'products');
        $data = $this->normalize($data);

        $product = Product::query()->create($data);
        $this->syncPrimaryImage($product, $image, $imageUrl);

        return $product;
    }

    public function update(Product $product, array $data): Product
    {
        $image = $data['image'] ?? null;
        $imageUrl = trim((string) ($data['image_url'] ?? ''));
        unset($data['image'], $data['image_url']);

        $data['sku'] = $this->sku($data['sku'] ?? $product->sku, $product->id);
        $data['slug'] = Slug::unique($data['name'], 'products', 'slug', $product->id);
        $data = $this->normalize($data);

        $product->update($data);
        $this->syncPrimaryImage($product, $image, $imageUrl ?: $product->primaryImage?->url);

        return $product;
    }

    public function delete(Product $product): void
    {
        if ($product->orderItems()->exists()) {
            throw ValidationException::withMessages([
                'product' => 'لا يمكن حذف المنتج لارتباطه بطلبات سابقة. يمكنك إخفاءه بدلاً من الحذف.',
            ]);
        }

        foreach ($product->images as $image) {
            Media::delete($image->url);
        }
        $product->images()->delete();
        $product->delete();
    }

    private function normalize(array $data): array
    {
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['stock'] = (int) ($data['stock'] ?? 0);
        $data['piece_count'] = isset($data['piece_count']) && $data['piece_count'] !== '' && $data['piece_count'] !== null
            ? (int) $data['piece_count']
            : null;
        $data['weight_label'] = trim((string) ($data['weight_label'] ?? '')) ?: null;
        $data['quantity_label'] = trim((string) ($data['quantity_label'] ?? '')) ?: null;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $data['discount_price'] = $data['discount_price'] !== null && $data['discount_price'] !== ''
            ? $data['discount_price']
            : null;
        $data['benefits'] = $this->lines($data['benefits'] ?? []);
        $data['keywords'] = $this->csv($data['keywords'] ?? []);

        return $data;
    }

    private function sku(?string $sku, ?int $ignoreId = null): string
    {
        $sku = strtoupper(trim((string) $sku));
        if ($sku === '') {
            $sku = 'SKU-'.strtoupper(Str::random(6));
        }

        $exists = Product::query()
            ->where('sku', $sku)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'sku' => 'رمز المنتج مستخدم مسبقاً.',
            ]);
        }

        return $sku;
    }

    private function syncPrimaryImage(Product $product, mixed $file, ?string $fallbackUrl): void
    {
        $url = Media::store($file, 'products', $product->primaryImage?->url) ?: $fallbackUrl;
        if (! $url) {
            return;
        }

        $primary = $product->primaryImage;
        if ($primary) {
            $primary->update(['url' => $url, 'alt' => $product->name]);

            return;
        }

        $product->images()->create([
            'url' => $url,
            'alt' => $product->name,
            'is_primary' => true,
            'sort_order' => 0,
        ]);
    }

    /**
     * @return list<string>
     */
    private function lines(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value)));
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value) ?: [])));
    }

    /**
     * @return list<string>
     */
    private function csv(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value)));
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }
}
