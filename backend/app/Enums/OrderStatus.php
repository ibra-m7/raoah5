<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Preparing = 'preparing';
    case OnTheWay = 'on_the_way';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'في انتظار التأكيد',
            self::Preparing => 'جاري التحضير',
            self::OnTheWay => 'في الطريق إليك',
            self::Delivered => 'تم التسليم',
            self::Cancelled => 'ملغي',
        };
    }

    public function isActive(): bool
    {
        return $this !== self::Cancelled && $this !== self::Delivered;
    }

    public function canBeCancelledByCustomer(): bool
    {
        return $this === self::Pending || $this === self::Preparing;
    }
}
