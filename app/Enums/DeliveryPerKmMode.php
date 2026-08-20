<?php

namespace App\Enums;

enum DeliveryPerKmMode: string
{
    case Entire = 'entire';
    case Extra = 'extra';

    public function label(): string
    {
        return match ($this) {
            self::Entire => 'كل المسافة من موقع المتجر',
            self::Extra => 'الزيادة عن الحد الأدنى فقط',
        };
    }
}
