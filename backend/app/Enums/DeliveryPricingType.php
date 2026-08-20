<?php

namespace App\Enums;

enum DeliveryPricingType: string
{
    case Free = 'free';
    case Flat = 'flat';
    case PerKm = 'per_km';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'مجاني',
            self::Flat => 'سعر ثابت',
            self::PerKm => 'لكل كيلومتر',
        };
    }
}
