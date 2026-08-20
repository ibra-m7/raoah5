<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Support\Constants;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OfferService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'primaryImage'])
            ->whereNotNull('discount_price')
            ->whereColumn('discount_price', '<', 'price')
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->search($search))
            ->orderByDesc('updated_at')
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();
    }

    public function productOptions(): Collection
    {
        return Product::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'discount_price']);
    }

    public function apply(array $data): Product
    {
        $product = Product::query()->findOrFail($data['product_id']);
        $discount = (float) $data['discount_price'];

        if ($discount <= 0 || $discount >= (float) $product->price) {
            throw ValidationException::withMessages([
                'discount_price' => 'سعر العرض يجب أن يكون أقل من السعر الأصلي وأكبر من صفر.',
            ]);
        }

        $product->update([
            'discount_price' => $discount,
            'is_featured' => (bool) ($data['is_featured'] ?? $product->is_featured),
        ]);

        return $product;
    }

    public function clear(Product $product): void
    {
        $product->update(['discount_price' => null]);
    }
}
