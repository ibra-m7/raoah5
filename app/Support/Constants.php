<?php

namespace App\Support;

/**
 * ثوابت المشروع — مطابقة لـ mobile/lib/core/utils/constants.dart
 */
final class Constants
{
    public const DEFAULT_PAGE_SIZE = 20;

    public const FREE_SHIPPING_THRESHOLD = 150.0;

    public const SHIPPING_FEE = 15.0;

    public const CURRENCY_CODE = 'SAR';

    public const SETTING_SHIPPING_FEE = 'shipping_fee';

    public const SETTING_FREE_SHIPPING_THRESHOLD = 'free_shipping_threshold';

    public const SETTING_DELIVERY_ENABLED = 'delivery_enabled';

    public const SETTING_DELIVERY_FIRST_ORDER_FREE = 'delivery_first_order_free';

    public const SETTING_DELIVERY_STORE_LAT = 'delivery_store_lat';

    public const SETTING_DELIVERY_STORE_LNG = 'delivery_store_lng';

    public const SETTING_DELIVERY_STORE_ADDRESS = 'delivery_store_address';

    public const SETTING_DELIVERY_MAX_KM = 'delivery_max_km';

    public const SETTING_DELIVERY_FALLBACK_FEE = 'delivery_fallback_fee';

    public const SETTING_STORE_NAME = 'store_name';

    public const SETTING_CURRENCY = 'currency';

    public const SETTING_BANK_IBAN = 'bank_iban';

    public const SETTING_BANK_NAME = 'bank_name';

    public const SETTING_AI_ENABLED = 'ai_enabled';

    public const SETTING_AI_GUESTS_ALLOWED = 'ai_guests_allowed';

    public const SETTING_AI_NAME = 'ai_assistant_name';

    public const SETTING_AI_WELCOME = 'ai_welcome_message';

    public const SETTING_AI_SYSTEM_PROMPT = 'ai_system_prompt';

    public const SETTING_AI_MAX_PRODUCTS = 'ai_max_products';

    public const SETTING_AI_MODEL = 'ai_gemini_model';

    public const SETTING_MARKETING_SOLD_COUNT = 'marketing_sold_count';

    public const SETTING_MARKETING_SOLD_SCOPE = 'marketing_sold_scope';

    public const SETTING_MARKETING_SOLD_PRODUCT_IDS = 'marketing_sold_product_ids';

    public const SETTING_FALLBACK_PRODUCT_IMAGE = 'fallback_product_image';

    public const AI_DEFAULT_MAX_PRODUCTS = 6;

    public const AI_DEFAULT_MODEL = 'gemini-3.6-flash';
}
