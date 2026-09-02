<?php

use App\Models\Setting;
use App\Support\Constants;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::query()->updateOrCreate(
            ['key' => Constants::SETTING_OTP_BYPASS_PHONES],
            ['value' => json_encode(['967778396448', '967777234341'])],
        );
    }

    public function down(): void
    {
        Setting::query()
            ->where('key', Constants::SETTING_OTP_BYPASS_PHONES)
            ->delete();
    }
};
