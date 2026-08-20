<?php

namespace App\Services\Admin;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponType;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Support\Constants;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CouponAdminService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Coupon::query()
            ->withCount('redemptions')
            ->search($filters['q'] ?? null)
            ->when(($filters['status'] ?? '') === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? '') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();
    }

    public function create(array $data): Coupon
    {
        $coupon = Coupon::query()->create($this->payload($data));
        $this->syncTargets($coupon, $data);

        return $coupon;
    }

    public function update(Coupon $coupon, array $data): Coupon
    {
        $coupon->update($this->payload($data));
        $this->syncTargets($coupon, $data);

        return $coupon;
    }

    public function delete(Coupon $coupon): void
    {
        if ($coupon->redemptions()->exists()) {
            throw ValidationException::withMessages([
                'code' => 'لا يمكن حذف كوبون استُخدم في طلبات. أخفِه من التطبيق بدلاً من الحذف.',
            ]);
        }

        $coupon->delete();
    }

    public function productOptions(): Collection
    {
        return Product::query()->orderBy('name')->get(['id', 'name']);
    }

    public function categoryOptions(): Collection
    {
        return Category::query()->orderBy('name')->get(['id', 'name', 'parent_id']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        $type = CouponType::from($data['type']);
        $value = $type === CouponType::FreeShipping ? 0 : (float) ($data['value'] ?? 0);

        return [
            'code' => Coupon::normalizeCode($data['code'] ?? ''),
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'type' => $type,
            'value' => $value,
            'min_subtotal' => (float) ($data['min_subtotal'] ?? 0),
            'max_discount' => $data['max_discount'] ?? null,
            'applies_to' => CouponAppliesTo::from($data['applies_to']),
            'usage_limit' => $data['usage_limit'] ?? null,
            'usage_limit_per_user' => (int) ($data['usage_limit_per_user'] ?? 1),
            'first_order_only' => (bool) ($data['first_order_only'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncTargets(Coupon $coupon, array $data): void
    {
        $applies = CouponAppliesTo::from($data['applies_to']);
        $coupon->products()->sync($applies === CouponAppliesTo::Products ? ($data['product_ids'] ?? []) : []);
        $coupon->categories()->sync($applies === CouponAppliesTo::Categories ? ($data['category_ids'] ?? []) : []);
    }
}
