<?php

namespace App\Enums;

enum ProductRelationType: string
{
    case Complementary = 'complementary';
    case Upsell = 'upsell';
    case Gift = 'gift';
}
