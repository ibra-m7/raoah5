<?php

namespace App\Services\Coupons;

use App\Models\Coupon;

class CouponQuote
{
    public function __construct(
        public readonly Coupon $coupon,
        public readonly float $eligibleSubtotal,
        public readonly float $discount,
        public readonly bool $freeShipping,
        public readonly string $message,
    ) {}

    public function toArray(float $subtotal, float $shippingFee, float $total): array
    {
        return [
            'code' => $this->coupon->code,
            'title' => $this->coupon->title ?: $this->coupon->code,
            'type' => $this->coupon->type->value,
            'type_label' => $this->coupon->type->label(),
            'message' => $this->message,
            'eligible_subtotal' => round($this->eligibleSubtotal, 2),
            'discount_amount' => round($this->discount, 2),
            'free_shipping' => $this->freeShipping,
            'subtotal' => round($subtotal, 2),
            'shipping_fee' => round($shippingFee, 2),
            'total' => round($total, 2),
        ];
    }
}
