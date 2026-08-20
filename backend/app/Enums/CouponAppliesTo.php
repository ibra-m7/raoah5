<?php

namespace App\Enums;

enum CouponAppliesTo: string
{
    case All = 'all';
    case Products = 'products';
    case Categories = 'categories';

    public function label(): string
    {
        return match ($this) {
            self::All => 'كل المنتجات',
            self::Products => 'منتجات محددة',
            self::Categories => 'أقسام محددة',
        };
    }
}
