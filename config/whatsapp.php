<?php

return [

    /*
    |--------------------------------------------------------------------------
    | قناة إرسال واتساب
    |--------------------------------------------------------------------------
    |
    | log      : يكتب الرسالة في سجل Laravel (للتطوير المحلي)
    | ultramsg : إرسال من رقم واتساب مربوط عبر UltraMsg
    | meta     : WhatsApp Cloud API الرسمية (Meta)
    |
    */
    'driver' => env('WHATSAPP_DRIVER', 'log'),

    /** رقم واتساب الشركة — 778396448 */
    'from_number' => env('WHATSAPP_FROM_NUMBER', '967778396448'),

    'otp_ttl' => (int) env('OTP_TTL_SECONDS', 300),

    'resend_seconds' => (int) env('OTP_RESEND_SECONDS', 60),

    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),

    'country_code' => env('PHONE_COUNTRY_CODE', '966'),

    'meta' => [
        'token' => env('WHATSAPP_CLOUD_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'template' => env('WHATSAPP_OTP_TEMPLATE', 'otp_login'),
        'template_language' => env('WHATSAPP_OTP_TEMPLATE_LANG', 'ar'),
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v21.0'),
    ],

    'ultramsg' => [
        'instance_id' => env('ULTRAMSG_INSTANCE_ID'),
        'token' => env('ULTRAMSG_TOKEN'),
        'base_url' => env('ULTRAMSG_BASE_URL', 'https://api.ultramsg.com'),
    ],

];
