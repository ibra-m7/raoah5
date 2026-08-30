<?php

namespace App\Services\Admin;

use App\Enums\BannerLinkType;
use App\Enums\ProductRelationType;
use App\Enums\PromoType;
use App\Jobs\GenerateProductCopyJob;
use App\Jobs\RefreshProductRecommendations;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductRelation;
use App\Models\Setting;
use App\Support\AppStrings;
use App\Support\Constants;
use App\Support\Media;
use App\Support\Slug;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Product::query()
            ->sellable()
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

    /**
     * منتجات محددة فقط — لا تحميل الكتالوج كاملاً في النماذج.
     *
     * @param  list<int|string>|int|string|null  $ids
     * @return Collection<int, Product>
     */
    public function pickerItems(mixed $ids): Collection
    {
        if ($ids instanceof \Illuminate\Support\Enumerable) {
            $ids = $ids->all();
        }

        $ordered = collect(is_array($ids) ? $ids : [$ids])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($ordered->isEmpty()) {
            return collect();
        }

        $products = Product::query()
            ->whereIn('id', $ordered)
            ->get(['id', 'name', 'sku', 'barcode', 'price'])
            ->keyBy('id');

        return $ordered
            ->map(fn (int $id) => $products->get($id))
            ->filter()
            ->values();
    }

    /**
     * @param  list<int>  $excludeIds
     * @return list<array{id: int, name: string, sku: string, barcode: string, price: float, price_label: string}>
     */
    public function searchPicker(
        string $term,
        ?int $exceptId = null,
        array $excludeIds = [],
        int $limit = 20,
        ?bool $giftOnly = null,
        bool $excludeGifts = false,
    ): array {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $exclude = collect($excludeIds)
            ->map(fn ($id) => (int) $id)
            ->when($exceptId, fn ($ids) => $ids->push((int) $exceptId))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->all();

        return Product::query()
            ->search($term)
            ->when($giftOnly === true, fn ($query) => $query->gift())
            ->when($giftOnly !== true && $excludeGifts, fn ($query) => $query->sellable())
            ->when($exclude !== [], fn ($query) => $query->whereNotIn('id', $exclude))
            ->orderBy('name')
            ->limit(max(1, min($limit, 40)))
            ->get(['id', 'name', 'sku', 'barcode', 'price'])
            ->map(fn (Product $product) => $this->formatPickerItem($product))
            ->all();
    }

    /**
     * @return array{id: int, name: string, sku: string, barcode: string, price: float, price_label: string}
     */
    public function formatPickerItem(Product $product): array
    {
        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'sku' => (string) ($product->sku ?? ''),
            'barcode' => (string) ($product->barcode ?? ''),
            'price' => (float) $product->price,
            'price_label' => number_format((float) $product->price, 2).' '.AppStrings::CURRENCY,
        ];
    }

    public function create(array $data): Product
    {
        return $this->persist(new Product, $data, creating: true);
    }

    public function update(Product $product, array $data): Product
    {
        $data['skip_ai_copy'] = true;

        return $this->persist($product, $data, creating: false);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persist(Product $product, array $data, bool $creating): Product
    {
        $image = $data['image'] ?? null;
        $imageUrl = trim((string) ($data['image_url'] ?? ''));
        $gallery = $data['gallery'] ?? [];
        $galleryUrls = $data['gallery_urls'] ?? '';
        $deleteIds = $data['delete_image_ids'] ?? [];
        $skipAiCopy = (bool) ($data['skip_ai_copy'] ?? false);
        $complementaryIds = $data['complementary_product_ids'] ?? [];
        $giftProductId = $data['gift_product_id'] ?? null;
        unset(
            $data['image'],
            $data['image_url'],
            $data['gallery'],
            $data['gallery_urls'],
            $data['delete_image_ids'],
            $data['skip_ai_copy'],
            $data['complementary_product_ids'],
            $data['gift_product_id'],
        );

        $data['sku'] = $this->sku($data['sku'] ?? ($creating ? null : $product->sku), $creating ? null : $product->id);
        $data['slug'] = Slug::unique($data['name'], 'products', 'slug', $creating ? null : $product->id);
        $needsCopy = $creating && ! $skipAiCopy && $this->needsGeneratedCopy($data);
        $data = $this->normalize($data);

        if ($creating) {
            $product = Product::query()->create($data);
        } else {
            $product->update($data);
            $this->deleteGalleryImages($product, $deleteIds);
        }

        $this->syncPrimaryImage($product, $image, $imageUrl ?: $product->primaryImage?->url);
        $this->syncGallery($product, $gallery, $galleryUrls);
        $this->syncComplementary($product, $complementaryIds);
        $this->syncGift($product, $giftProductId);

        if ($needsCopy) {
            GenerateProductCopyJob::dispatch($product->id)->afterResponse();
        }

        if (! $skipAiCopy) {
            RefreshProductRecommendations::dispatch($product->id)->afterResponse();
        }

        return $product;
    }

    /**
     * @param  list<int|string>|mixed  $ids
     */
    public function syncComplementary(Product $product, mixed $ids): void
    {
        $selected = collect(is_array($ids) ? $ids : [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0 && $id !== (int) $product->id)
            ->unique()
            ->values()
            ->all();

        ProductRelation::query()
            ->where('product_id', $product->id)
            ->where('type', ProductRelationType::Complementary)
            ->where('source', 'manual')
            ->when($selected !== [], fn ($query) => $query->whereNotIn('related_product_id', $selected))
            ->delete();

        foreach ($selected as $index => $relatedId) {
            ProductRelation::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'related_product_id' => $relatedId,
                    'type' => ProductRelationType::Complementary,
                ],
                [
                    'sort_order' => $index,
                    'source' => 'manual',
                ],
            );
        }
    }

    public function syncGift(Product $product, mixed $giftProductId): void
    {
        $giftId = (int) $giftProductId;
        if ($giftId <= 0 || $giftId === (int) $product->id) {
            ProductRelation::query()
                ->where('product_id', $product->id)
                ->where('type', ProductRelationType::Gift)
                ->delete();

            return;
        }

        ProductRelation::query()
            ->where('product_id', $product->id)
            ->where('type', ProductRelationType::Gift)
            ->where('related_product_id', '!=', $giftId)
            ->delete();

        ProductRelation::query()->updateOrCreate(
            [
                'product_id' => $product->id,
                'related_product_id' => $giftId,
                'type' => ProductRelationType::Gift,
            ],
            [
                'sort_order' => 0,
                'source' => 'manual',
            ],
        );
    }

    /**
     * @param  array{name: string, category_id?: int|null, price?: float|int|string|null, stock: int, image?: mixed}  $data
     */
    public function createQuickGift(array $data): Product
    {
        return $this->create([
            'name' => $data['name'],
            'category_id' => $this->resolveGiftCategoryId($data['category_id'] ?? null),
            'price' => (float) ($data['price'] ?? 0),
            'stock' => (int) $data['stock'],
            'image' => $data['image'] ?? null,
            'is_gift' => true,
            'skip_ai_copy' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function resolveGiftCategoryId(?int $categoryId): int
    {
        if ($categoryId && $categoryId > 0) {
            return $categoryId;
        }

        $existing = Category::query()
            ->where('name', 'هدايا')
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $parentId = Category::query()
            ->whereNull('parent_id')
            ->where('name', 'المقاضي')
            ->value('id');

        if (! $parentId) {
            $parent = app(CategoryService::class)->create([
                'name' => 'المقاضي',
                'parent_id' => null,
                'is_active' => true,
                'sort_order' => 0,
            ]);
            $parentId = $parent->id;
        }

        $category = app(CategoryService::class)->create([
            'name' => 'هدايا',
            'parent_id' => $parentId,
            'is_active' => true,
            'sort_order' => 999,
        ]);

        return (int) $category->id;
    }

    /**
     * @param  list<int>  $mainIds
     * @return list<int>
     */
    public function linkGiftToMainProducts(int $giftId, array $mainIds): array
    {
        $linked = [];

        foreach ($mainIds as $mainId) {
            $mainId = (int) $mainId;
            if ($mainId <= 0 || $mainId === $giftId) {
                continue;
            }

            $main = Product::query()->find($mainId);
            if ($main === null) {
                continue;
            }

            $this->syncGift($main, $giftId);
            $linked[] = $mainId;
        }

        return $linked;
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

    public function deleteAll(): int
    {
        $count = Product::withTrashed()->count();
        if ($count === 0) {
            return 0;
        }

        $imageUrls = ProductImage::query()->pluck('url');

        DB::transaction(function () {
            Banner::query()
                ->where('link_type', BannerLinkType::Product)
                ->update([
                    'link_type' => BannerLinkType::None,
                    'link_id' => null,
                ]);

            Setting::setValue(Constants::SETTING_MARKETING_SOLD_PRODUCT_IDS, '[]');
            ProductRelation::query()->delete();
            ProductImage::query()->delete();
            Product::withTrashed()->forceDelete();
        });

        foreach ($imageUrls as $url) {
            Media::delete(is_string($url) ? $url : null);
        }

        return $count;
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
     * }  $copy
     */
    public function applyGeneratedCopy(Product $product, array $copy): void
    {
        $product->update([
            'description' => $copy['description'] !== ''
                ? $copy['description']
                : $product->description,
            'category_id' => $copy['category_id'] ?? $product->category_id,
            'benefits' => $copy['benefits'] !== []
                ? $copy['benefits']
                : $product->benefits,
            'keywords' => $copy['keywords'] !== []
                ? $copy['keywords']
                : $product->keywords,
            'usage_instructions' => $copy['usage_instructions'] !== ''
                ? $copy['usage_instructions']
                : $product->usage_instructions,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function needsGeneratedCopy(array $data): bool
    {
        return $this->lines($data['benefits'] ?? []) === []
            || $this->csv($data['keywords'] ?? []) === []
            || trim((string) ($data['usage_instructions'] ?? '')) === '';
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
        $data['is_gift'] = (bool) ($data['is_gift'] ?? false);
        if (array_key_exists('discount_price', $data)) {
            $discount = $data['discount_price'];
            $data['discount_price'] = $discount !== null && $discount !== '' && (float) $discount > 0
                ? $discount
                : null;
            if ($data['discount_price'] === null) {
                $data['promo_type'] = null;
            } elseif (empty($data['promo_type'])) {
                $data['promo_type'] = PromoType::Discount;
            }
        }
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

    public function clearPrimaryImageIfMissingLocal(Product $product): void
    {
        $primary = $product->primaryImage;
        if ($primary === null || ! Media::isMissingLocal($primary->url)) {
            return;
        }

        Media::delete($primary->url);
        $primary->delete();
    }

    public function attachPrimaryImageFromPath(Product $product, string $absolutePath): void
    {
        $path = Media::storePath($absolutePath, 'products', $product->primaryImage?->url);
        if (! $path) {
            return;
        }

        $primary = $product->primaryImage;
        if ($primary) {
            $primary->update(['url' => $path, 'alt' => $product->name]);

            return;
        }

        $product->images()->create([
            'url' => $path,
            'alt' => $product->name,
            'is_primary' => true,
            'sort_order' => 0,
        ]);
    }

    private function syncGallery(Product $product, mixed $files, mixed $urlsText): void
    {
        $next = (int) $product->images()->max('sort_order') + 1;
        foreach (is_array($files) ? $files : [] as $file) {
            $path = Media::store($file, 'products');
            if (! $path) {
                continue;
            }
            $product->images()->create([
                'url' => $path,
                'alt' => $product->name,
                'is_primary' => false,
                'sort_order' => $next++,
            ]);
        }

        foreach (preg_split('/\r\n|\r|\n/', (string) $urlsText) ?: [] as $line) {
            $url = trim($line);
            if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }
            $product->images()->create([
                'url' => $url,
                'alt' => $product->name,
                'is_primary' => false,
                'sort_order' => $next++,
            ]);
        }
    }

    /**
     * @param  list<int|string>  $ids
     */
    private function deleteGalleryImages(Product $product, array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return;
        }

        $images = $product->images()
            ->whereIn('id', $ids)
            ->where('is_primary', false)
            ->get();

        foreach ($images as $image) {
            Media::delete($image->url);
            $image->delete();
        }
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
