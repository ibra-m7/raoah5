<?php

namespace App\Enums;

enum BannerLinkType: string
{
    case Product = 'product';
    case Category = 'category';
    case Page = 'page';
    case Bundle = 'bundle';
    case Url = 'url';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Product => 'منتج',
            self::Category => 'قسم',
            self::Page => 'صفحة ترويجية',
            self::Bundle => 'سلة توفير',
            self::Url => 'رابط خارجي',
            self::None => 'بدون رابط',
        };
    }
}
