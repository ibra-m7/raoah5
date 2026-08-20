<?php

namespace Database\Seeders;

use App\Models\Courier;
use App\Support\Phone;
use Illuminate\Database\Seeder;

class CourierSeeder extends Seeder
{
    public function run(): void
    {
        $phone = Phone::normalize('777234341');
        if ($phone === null) {
            return;
        }

        Courier::query()->updateOrCreate(
            ['phone' => $phone],
            [
                'name' => 'موصل تجريبي',
                'password' => '123456',
                'is_active' => true,
            ],
        );
    }
}
