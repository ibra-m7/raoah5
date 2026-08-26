<?php

namespace App\Support;

use App\Enums\PaymentMethod;
use App\Models\Setting;
use App\Models\StorePaymentMethod;
use App\Services\Admin\SearchPlaceholderService;
use App\Support\Media;

final class StoreSettings
{
    public static function shippingFee(): float
    {
        return (float) Setting::getValue(Constants::SETTING_SHIPPING_FEE, Constants::SHIPPING_FEE);
    }

    public static function freeShippingThreshold(): float
    {
        return (float) Setting::getValue(
            Constants::SETTING_FREE_SHIPPING_THRESHOLD,
            Constants::FREE_SHIPPING_THRESHOLD,
        );
    }

    public static function currency(): string
    {
        return AppStrings::CURRENCY;
    }

    public static function bankIban(): string
    {
        return (string) Setting::getValue(Constants::SETTING_BANK_IBAN, '');
    }

    public static function bankName(): string
    {
        return (string) Setting::getValue(Constants::SETTING_BANK_NAME, 'البنك الأهلي السعودي');
    }

    public static function marketingSoldCount(): int
    {
        return max(0, (int) Setting::getValue(Constants::SETTING_MARKETING_SOLD_COUNT, 0));
    }

    public static function marketingSoldScope(): string
    {
        $scope = (string) Setting::getValue(Constants::SETTING_MARKETING_SOLD_SCOPE, 'all');

        return $scope === 'selected' ? 'selected' : 'all';
    }

    /**
     * @return list<int>|null null = لم يُحفظ بعد (يُعامل ككل المنتجات)
     */
    public static function marketingSoldProductIds(): ?array
    {
        $raw = Setting::getValue(Constants::SETTING_MARKETING_SOLD_PRODUCT_IDS, null);
        if ($raw === null || $raw === '') {
            return null;
        }

        $ids = json_decode((string) $raw, true);
        if (! is_array($ids)) {
            return null;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public static function marketingSoldCountFor(mixed $product): int
    {
        $count = self::marketingSoldCount();
        if ($count <= 0) {
            return 0;
        }

        if (self::marketingSoldScope() !== 'selected') {
            return $count;
        }

        $ids = self::marketingSoldProductIds() ?? [];
        $productId = is_object($product) ? (int) ($product->id ?? 0) : (int) $product;

        return in_array($productId, $ids, true) ? $count : 0;
    }

    public static function fallbackProductImageUrl(): string
    {
        $value = trim((string) Setting::getValue(Constants::SETTING_FALLBACK_PRODUCT_IMAGE, ''));
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        if (! str_starts_with($value, 'data:') && Media::isMissingLocal($value)) {
            return '';
        }

        return Media::absoluteUrl('media/fallback-product').'?v='.substr(sha1($value), 0, 10);
    }

    public static function payload(): array
    {
        return [
            'country' => 'SA',
            'country_name' => 'المملكة العربية السعودية',
            'currency' => self::currency(),
            'currency_code' => Constants::CURRENCY_CODE,
            'shipping_fee' => self::shippingFee(),
            'free_shipping_threshold' => self::freeShippingThreshold(),
            'delivery' => DeliverySettings::payload(),
            'phone_country_code' => Phone::countryCode(),
            'bank_iban' => self::bankIban(),
            'bank_name' => self::bankName(),
            'marketing_sold_count' => self::marketingSoldCount(),
            'fallback_product_image_url' => self::fallbackProductImageUrl(),
            'payment_methods' => self::checkoutPaymentMethods(),
            'search_placeholders' => SearchPlaceholderService::activePhrases(),
        ];
    }

    /**
     * @return list<array{id: string, label: string, hint: string, icon: string, icon_url: string}>
     */
    public static function checkoutPaymentMethods(): array
    {
        $fromStore = StorePaymentMethod::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (StorePaymentMethod $method) => $method->toCheckoutOption())
            ->values()
            ->all();

        if ($fromStore !== []) {
            return $fromStore;
        }

        return collect(PaymentMethod::checkoutOptions())
            ->map(fn (PaymentMethod $method) => [
                'id' => $method->value,
                'label' => $method->label(),
                'hint' => $method->hint(),
                'icon' => 'bi-credit-card',
                'icon_url' => '',
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function activePaymentSlugs(): array
    {
        return collect(self::checkoutPaymentMethods())
            ->pluck('id')
            ->filter()
            ->values()
            ->all();
    }
}
