<?php

namespace App\Services\Coupons;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Product;
use App\Models\User;
use App\Support\AppStrings;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CouponService
{
    /**
     * @param  Collection<int, array{product: Product, quantity: int, unit_price: float, line_total: float}>  $lines
     */
    public function quote(User $user, ?string $code, Collection $lines, ?int $exceptOrderId = null): ?CouponQuote
    {
        $normalized = Coupon::normalizeCode($code);
        if ($normalized === '') {
            return null;
        }

        /** @var Coupon|null $coupon */
        $coupon = Coupon::query()
            ->with(['products:id', 'categories:id'])
            ->where('code', $normalized)
            ->lockForUpdate()
            ->first();

        if ($coupon === null) {
            throw ValidationException::withMessages([
                'coupon_code' => 'كود الخصم غير صحيح.',
            ]);
        }

        $this->assertUsable($coupon, $user, $exceptOrderId);

        $eligible = $this->eligibleSubtotal($coupon, $lines);
        if ($eligible <= 0) {
            throw ValidationException::withMessages([
                'coupon_code' => 'هذا الكوبون لا ينطبق على منتجات سلتك.',
            ]);
        }

        $min = (float) $coupon->min_subtotal;
        if ($min > 0 && $eligible < $min) {
            $needed = number_format($min - $eligible, 2);
            throw ValidationException::withMessages([
                'coupon_code' => "أضف منتجات بقيمة {$needed} ".AppStrings::CURRENCY.' على الأقل لتفعيل الكوبون.',
            ]);
        }

        $discount = $this->discountAmount($coupon, $eligible);
        $freeShipping = $coupon->type === CouponType::FreeShipping;

        if (! $freeShipping && $discount <= 0) {
            throw ValidationException::withMessages([
                'coupon_code' => 'لا يمكن تطبيق هذا الكوبون على السلة الحالية.',
            ]);
        }

        $message = match ($coupon->type) {
            CouponType::Percent => 'تم تطبيق خصم '.rtrim(rtrim(number_format((float) $coupon->value, 2), '0'), '.').'٪',
            CouponType::Fixed => 'تم تطبيق خصم '.number_format($discount, 2).' '.AppStrings::CURRENCY,
            CouponType::FreeShipping => 'تم تفعيل التوصيل المجاني',
        };

        return new CouponQuote(
            coupon: $coupon,
            eligibleSubtotal: $eligible,
            discount: $discount,
            freeShipping: $freeShipping,
            message: $message,
        );
    }

    public function redeem(Coupon $coupon, User $user, int $orderId, float $discount): void
    {
        $coupon->redemptions()->create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'discount_amount' => $discount,
        ]);
    }

    public function releaseForOrder(int $orderId): void
    {
        CouponRedemption::query()->where('order_id', $orderId)->delete();
    }

    private function assertUsable(Coupon $coupon, User $user, ?int $exceptOrderId = null): void
    {
        if (! $coupon->is_active) {
            throw ValidationException::withMessages([
                'coupon_code' => 'هذا الكوبون غير متاح حالياً.',
            ]);
        }

        $now = now();
        if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'هذا الكوبون لم يبدأ بعد.',
            ]);
        }
        if ($coupon->ends_at && $now->gt($coupon->ends_at)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'انتهت صلاحية هذا الكوبون.',
            ]);
        }

        if ($coupon->usage_limit !== null) {
            $used = $coupon->redemptions()
                ->when($exceptOrderId, fn ($query) => $query->where('order_id', '!=', $exceptOrderId))
                ->count();
            if ($used >= $coupon->usage_limit) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'تم استهلاك الحد الأقصى لاستخدام هذا الكوبون.',
                ]);
            }
        }

        $perUser = max(1, (int) $coupon->usage_limit_per_user);
        $userUsed = $coupon->redemptions()
            ->where('user_id', $user->id)
            ->when($exceptOrderId, fn ($query) => $query->where('order_id', '!=', $exceptOrderId))
            ->count();
        if ($userUsed >= $perUser) {
            throw ValidationException::withMessages([
                'coupon_code' => 'سبق لك استخدام هذا الكوبون.',
            ]);
        }

        if ($coupon->first_order_only) {
            $hasOrder = $user->orders()
                ->where('status', '!=', OrderStatus::Cancelled->value)
                ->when($exceptOrderId, fn ($query) => $query->where('id', '!=', $exceptOrderId))
                ->exists();
            if ($hasOrder) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'هذا الكوبون للطلب الأول فقط.',
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, array{product: Product, quantity: int, unit_price: float, line_total: float}>  $lines
     */
    private function eligibleSubtotal(Coupon $coupon, Collection $lines): float
    {
        $applies = $coupon->applies_to ?? CouponAppliesTo::All;
        $productIds = $coupon->products->pluck('id')->map(fn ($id) => (int) $id)->all();
        $categoryIds = $this->expandedCategoryIds($coupon);

        $sum = 0.0;
        foreach ($lines as $line) {
            /** @var Product $product */
            $product = $line['product'];
            $ok = match ($applies) {
                CouponAppliesTo::Products => in_array((int) $product->id, $productIds, true),
                CouponAppliesTo::Categories => in_array((int) $product->category_id, $categoryIds, true),
                default => true,
            };
            if ($ok) {
                $sum += (float) $line['line_total'];
            }
        }

        return round($sum, 2);
    }

    /**
     * @return list<int>
     */
    private function expandedCategoryIds(Coupon $coupon): array
    {
        $ids = $coupon->categories->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($ids === []) {
            return [];
        }

        $children = Category::query()
            ->whereIn('parent_id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique([...$ids, ...$children]));
    }

    private function discountAmount(Coupon $coupon, float $eligible): float
    {
        $discount = match ($coupon->type) {
            CouponType::Percent => round($eligible * ((float) $coupon->value / 100), 2),
            CouponType::Fixed => min((float) $coupon->value, $eligible),
            CouponType::FreeShipping => 0.0,
        };

        if ($coupon->max_discount !== null && (float) $coupon->max_discount > 0) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return round(max(0, min($discount, $eligible)), 2);
    }
}
