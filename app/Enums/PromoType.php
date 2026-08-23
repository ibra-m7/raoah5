<?php

namespace App\Enums;

enum PromoType: string
{
    case Discount = 'discount';
    case Offer = 'offer';

    public function label(): string
    {
        return match ($this) {
            self::Discount => 'خصم',
            self::Offer => 'عرض',
        };
    }

    public function plural(): string
    {
        return match ($this) {
            self::Discount => 'الخصومات',
            self::Offer => 'العروض',
        };
    }

    public function addLabel(): string
    {
        return match ($this) {
            self::Discount => 'إضافة خصم',
            self::Offer => 'إضافة عرض',
        };
    }

    public static function fromRequest(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Discount;
    }
}
