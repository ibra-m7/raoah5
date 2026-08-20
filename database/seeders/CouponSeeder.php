<?php

namespace Database\Seeders;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'code' => 'WELCOME10',
                'title' => 'خصم الترحيب 10٪',
                'description' => 'للطلب الأول فقط — سقف 30 ر.س',
                'type' => CouponType::Percent,
                'value' => 10,
                'min_subtotal' => 50,
                'max_discount' => 30,
                'applies_to' => CouponAppliesTo::All,
                'usage_limit' => null,
                'usage_limit_per_user' => 1,
                'first_order_only' => true,
                'is_active' => true,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addYear(),
            ],
            [
                'code' => 'SAVE20',
                'title' => 'خصم 20 ر.س',
                'description' => 'على سلة 100 ر.س فأكثر',
                'type' => CouponType::Fixed,
                'value' => 20,
                'min_subtotal' => 100,
                'max_discount' => null,
                'applies_to' => CouponAppliesTo::All,
                'usage_limit' => 500,
                'usage_limit_per_user' => 2,
                'first_order_only' => false,
                'is_active' => true,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonths(3),
            ],
            [
                'code' => 'FREESHIP',
                'title' => 'توصيل مجاني',
                'description' => 'يلغي رسوم التوصيل',
                'type' => CouponType::FreeShipping,
                'value' => 0,
                'min_subtotal' => 40,
                'max_discount' => null,
                'applies_to' => CouponAppliesTo::All,
                'usage_limit' => null,
                'usage_limit_per_user' => 3,
                'first_order_only' => false,
                'is_active' => true,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonths(6),
            ],
        ];

        foreach ($rows as $row) {
            Coupon::query()->updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
