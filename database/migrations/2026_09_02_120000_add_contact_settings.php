<?php

use App\Models\Setting;
use App\Support\Constants;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::query()->updateOrCreate(
            ['key' => Constants::SETTING_CUSTOMER_SERVICE_NUMBERS],
            ['value' => '[]'],
        );

        Setting::query()->updateOrCreate(
            ['key' => Constants::SETTING_MESSAGE_US_PHONE],
            ['value' => '967778396448'],
        );
    }

    public function down(): void
    {
        Setting::query()->whereIn('key', [
            Constants::SETTING_CUSTOMER_SERVICE_NUMBERS,
            Constants::SETTING_MESSAGE_US_PHONE,
        ])->delete();
    }
};
