<?php

namespace App\Enums;

enum NotificationType: string
{
    case Order = 'order';
    case Promo = 'promo';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Order => 'طلب',
            self::Promo => 'عرض',
            self::General => 'عام',
        };
    }
}
