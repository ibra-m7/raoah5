<?php

namespace App\Enums;

enum DynamicPagePlacement: string
{
    case None = 'none';
    case SearchBestOffers = 'search_best_offers';
    case SearchLegendary = 'search_legendary';

    public function label(): string
    {
        return match ($this) {
            self::None => 'لا تظهر كقسم — فقط عبر البنر أو الرابط',
            self::SearchBestOffers => 'شاشة البحث · أفضل العروض',
            self::SearchLegendary => 'شاشة البحث · عروض أسطورية',
        };
    }
}
