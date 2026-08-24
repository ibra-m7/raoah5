<?php

namespace App\Models;

use App\Support\Constants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        if ($key === Constants::SETTING_FALLBACK_PRODUCT_IMAGE) {
            $value = static::query()->where('key', $key)->value('value');

            return $value ?? $default;
        }

        $settings = Cache::remember('app_settings', 3600, function () {
            return static::query()
                ->where('key', '!=', Constants::SETTING_FALLBACK_PRODUCT_IMAGE)
                ->pluck('value', 'key');
        });

        return $settings[$key] ?? $default;
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_scalar($value) || $value === null ? $value : json_encode($value)]
        );

        Cache::forget('app_settings');
        Cache::forget('fallback_product_image_url');
        Cache::forget('fallback_product_image_meta');
    }
}
