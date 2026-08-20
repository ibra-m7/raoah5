<?php

namespace App\Support;

use App\Models\DeliveryPerk;
use App\Models\DeliveryRule;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

final class DeliverySettings
{
    public static function enabled(): bool
    {
        return self::bool(Constants::SETTING_DELIVERY_ENABLED, true);
    }

    public static function firstOrderFree(): bool
    {
        return self::bool(Constants::SETTING_DELIVERY_FIRST_ORDER_FREE, true);
    }

    public static function storeLat(): ?float
    {
        return self::nullableFloat(Constants::SETTING_DELIVERY_STORE_LAT);
    }

    public static function storeLng(): ?float
    {
        return self::nullableFloat(Constants::SETTING_DELIVERY_STORE_LNG);
    }

    public static function storeAddress(): string
    {
        return (string) Setting::getValue(Constants::SETTING_DELIVERY_STORE_ADDRESS, '');
    }

    public static function maxKm(): ?float
    {
        return self::nullableFloat(Constants::SETTING_DELIVERY_MAX_KM);
    }

    public static function fallbackFee(): float
    {
        $value = self::nullableFloat(Constants::SETTING_DELIVERY_FALLBACK_FEE);

        return $value ?? StoreSettings::shippingFee();
    }

    public static function hasStoreLocation(): bool
    {
        return self::storeLat() !== null && self::storeLng() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(): array
    {
        return [
            'enabled' => self::enabled(),
            'first_order_free' => self::firstOrderFree(),
            'has_store_location' => self::hasStoreLocation(),
            'store_lat' => self::storeLat(),
            'store_lng' => self::storeLng(),
            'store_address' => self::storeAddress(),
            'max_km' => self::maxKm(),
            'fallback_fee' => self::fallbackFee(),
            'rules' => self::activeRulesPayload(),
            'perks' => self::activePerksPayload(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function activeRulesPayload(): array
    {
        try {
            if (! Schema::hasTable('delivery_rules')) {
                return [];
            }

            return DeliveryRule::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('min_km')
                ->get()
                ->map(fn (DeliveryRule $rule) => [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'min_km' => (float) $rule->min_km,
                    'max_km' => $rule->max_km !== null ? (float) $rule->max_km : null,
                    'pricing_type' => $rule->pricing_type->value,
                    'amount' => (float) $rule->amount,
                    'per_km_mode' => $rule->per_km_mode->value,
                    'range' => $rule->rangeLabel(),
                    'label' => $rule->priceLabel(),
                ])
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function activePerksPayload(): array
    {
        try {
            if (! Schema::hasTable('delivery_perks')) {
                return [];
            }

            return DeliveryPerk::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (DeliveryPerk $perk) => [
                    'id' => $perk->id,
                    'name' => $perk->name,
                    'trigger_type' => $perk->trigger_type->value,
                    'min_orders' => $perk->min_orders,
                    'reward_type' => $perk->reward_type->value,
                    'reward_value' => (float) $perk->reward_value,
                    'trigger' => $perk->triggerLabel(),
                    'label' => $perk->rewardLabel(),
                ])
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private static function bool(string $key, bool $default): bool
    {
        $value = Setting::getValue($key, $default ? '1' : '0');

        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }

    private static function nullableFloat(string $key): ?float
    {
        $value = Setting::getValue($key, '');
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
