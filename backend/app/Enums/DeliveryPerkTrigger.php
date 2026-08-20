<?php

namespace App\Enums;

enum DeliveryPerkTrigger: string
{
    case MinOrders = 'min_orders';
    case EveryNth = 'every_nth';

    public function label(): string
    {
        return match ($this) {
            self::MinOrders => 'بعد عدد معيّن من الطلبات',
            self::EveryNth => 'كل عدد معيّن من الطلبات',
        };
    }
}
