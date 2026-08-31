<?php

use App\Models\Setting;
use App\Support\Constants;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::setValue(Constants::SETTING_PICKUP_ENABLED, '1');
    }

    public function down(): void
    {
        Setting::query()->where('key', Constants::SETTING_PICKUP_ENABLED)->delete();
    }
};
