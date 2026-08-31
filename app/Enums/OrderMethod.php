<?php

namespace App\Enums;

enum OrderMethod: string
{
    case Delivery = 'delivery';
    case Pickup = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::Delivery => 'توصيل',
            self::Pickup => 'استلام من المركز',
        };
    }

    public function isPickup(): bool
    {
        return $this === self::Pickup;
    }
}
