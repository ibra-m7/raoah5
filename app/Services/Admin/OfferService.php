<?php

namespace App\Services\Admin;

use App\Enums\PromoType;
use App\Models\Product;
use App\Support\Constants;
use App\Support\Media;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OfferService
{
    public function paginate(PromoType $type, array $filters = []): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'primaryImage'])
            ->onPromo($type)
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->search($search))
            ->orderByDesc('updated_at')
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();
    }

    public function counts(): array
    {
        return [
            PromoType::Discount->value => Product::query()->onPromo(PromoType::Discount)->count(),
            PromoType::Offer->value => Product::query()->onPromo(PromoType::Offer)->count(),
        ];
    }

    public function availableProducts(?string $search = null, ?int $exceptId = null, int $limit = 40): Collection
    {
        return Product::query()
            ->active()
            ->with('primaryImage')
            ->withoutPromo($exceptId)
            ->when($search, fn ($query, $term) => $query->search($term))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'price', 'discount_price', 'sku']);
    }

    public function availablePayload(?string $search = null, ?int $exceptId = null): array
    {
        return $this->availableProducts($search, $exceptId)->map(fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => (float) $product->price,
            'image' => Media::url($product->primaryImage?->url) ?: '',
        ])->values()->all();
    }

    /**
     * @param  list<int>  $productIds
     */
    public function applyMany(PromoType $type, array $productIds, array $data): int
    {
        $mode = $data['mode'] ?? 'price';
        $applied = 0;

        foreach (array_unique($productIds) as $productId) {
            $product = Product::query()->find($productId);
            if (! $product) {
                continue;
            }

            if ($product->has_discount && $product->promo_type && $product->promo_type !== $type) {
                throw ValidationException::withMessages([
                    'product_ids' => 'المنتج «'.$product->name.'» عليه '.$product->promo_type->label().' حالياً. ألغه أولاً.',
                ]);
            }

            $discount = $this->resolveDiscount($product, $mode, $data);

            $product->update([
                'discount_price' => $discount,
                'promo_type' => $type,
                'is_featured' => (bool) ($data['is_featured'] ?? $product->is_featured),
            ]);
            $applied++;
        }

        if ($applied === 0) {
            throw ValidationException::withMessages([
                'product_ids' => 'اختر منتجاً واحداً على الأقل.',
            ]);
        }

        return $applied;
    }

    public function apply(array $data): Product
    {
        $type = PromoType::fromRequest($data['promo_type'] ?? null);
        $this->applyMany($type, [(int) $data['product_id']], $data);

        return Product::query()->findOrFail($data['product_id']);
    }

    public function clear(Product $product): void
    {
        $product->update([
            'discount_price' => null,
            'promo_type' => null,
        ]);
    }

    private function resolveDiscount(Product $product, string $mode, array $data): float
    {
        $price = (float) $product->price;

        if ($mode === 'percent') {
            $percent = (float) ($data['percent'] ?? 0);
            if ($percent <= 0 || $percent >= 100) {
                throw ValidationException::withMessages([
                    'percent' => 'النسبة يجب أن تكون أكبر من 0 وأقل من 100.',
                ]);
            }
            $discount = round($price * (1 - ($percent / 100)), 2);
        } else {
            $discount = (float) ($data['discount_price'] ?? 0);
        }

        if ($discount <= 0 || $discount >= $price) {
            throw ValidationException::withMessages([
                'discount_price' => 'السعر بعد التخفيض يجب أن يكون أقل من سعر «'.$product->name.'» ('.number_format($price, 2).') وأكبر من صفر.',
            ]);
        }

        return $discount;
    }
}
