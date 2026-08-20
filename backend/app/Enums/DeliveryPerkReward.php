<?php

namespace App\Enums;

enum DeliveryPerkReward: string
{
    case Free = 'free';
    case Percent = 'percent';
    case Amount = 'amount';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'توصيل مجاني',
            self::Percent => 'خصم نسبة من التوصيل',
            self::Amount => 'خصم مبلغ من التوصيل',
        };
    }
}
