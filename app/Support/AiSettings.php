<?php

namespace App\Support;

use App\Models\Setting;

final class AiSettings
{
    /**
     * @return list<string>
     */
    public static function models(): array
    {
        return [
            'gemini-3.6-flash',
            'gemini-3.5-flash',
            'gemini-3.7-flash',
            'gemini-flash-latest',
            'gemini-flash-lite-latest',
        ];
    }

    /**
     * @return list<string>
     */
    public static function fallbacks(): array
    {
        return [
            'gemini-3.5-flash',
            'gemini-flash-lite-latest',
            'gemini-3.6-flash',
            'gemini-flash-latest',
        ];
    }

    public static function enabled(): bool
    {
        return filter_var(
            Setting::getValue(Constants::SETTING_AI_ENABLED, '1'),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public static function guestsAllowed(): bool
    {
        return filter_var(
            Setting::getValue(Constants::SETTING_AI_GUESTS_ALLOWED, '1'),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public static function name(): string
    {
        $name = trim((string) Setting::getValue(Constants::SETTING_AI_NAME, 'روعة'));

        return $name !== '' ? $name : 'روعة';
    }

    public static function welcome(): string
    {
        $welcome = trim((string) Setting::getValue(
            Constants::SETTING_AI_WELCOME,
            self::defaultWelcome()
        ));

        return $welcome !== '' ? $welcome : self::defaultWelcome();
    }

    public static function systemPrompt(): string
    {
        $prompt = trim((string) Setting::getValue(
            Constants::SETTING_AI_SYSTEM_PROMPT,
            self::defaultSystemPrompt()
        ));

        return $prompt !== '' ? $prompt : self::defaultSystemPrompt();
    }

    public static function maxProducts(): int
    {
        $max = (int) Setting::getValue(
            Constants::SETTING_AI_MAX_PRODUCTS,
            Constants::AI_DEFAULT_MAX_PRODUCTS
        );

        return max(2, min($max, 8));
    }

    public static function model(): string
    {
        $model = trim((string) Setting::getValue(
            Constants::SETTING_AI_MODEL,
            config('services.gemini.model', Constants::AI_DEFAULT_MODEL)
        ));

        $retired = [
            'gemini-1.5-flash' => 'gemini-3.6-flash',
            'gemini-2.0-flash' => 'gemini-3.6-flash',
            'gemini-2.0-flash-lite' => 'gemini-flash-lite-latest',
            'gemini-2.5-flash' => 'gemini-3.6-flash',
            'gemini-2.5-flash-lite' => 'gemini-flash-lite-latest',
        ];

        if (isset($retired[$model])) {
            return $retired[$model];
        }

        return in_array($model, self::models(), true) ? $model : Constants::AI_DEFAULT_MODEL;
    }

    public static function hasApiKey(): bool
    {
        return trim((string) config('services.gemini.key')) !== '';
    }

    public static function defaultWelcome(): string
    {
        return 'أهلاً بك في روعة الخمسة! أنا روعة، مساعدك للتسوق. اطلب منظفاً أو عرضاً وسأقترح لك منتجات حقيقية من المتجر.';
    }

    public static function defaultSystemPrompt(): string
    {
        return <<<'TXT'
أنت مساعدة تسوق عربية لمتجر «روعة الخمسة» في السعودية.
اسمك يظهر للعميل من إعدادات المتجر، وتتحدثين بلهجة واضحة وودودة دون مبالغة.
اقترحي منتجات حقيقية من كتالوج المتجر فقط، ولا تختلقي أسماء أو أسعاراً أو خصومات.
إذا طلب العميل نوعاً من المنتجات (منظفات، عروض، طعام…) اختاري عدة منتجات مناسبة من القائمة المرفقة.
إذا لم يوجد منتج مطابق، اعتذري بصدق وقدّمي أقرب البدائل المتاحة.
لا تناقشي مواضيع خارج المتجر أو الطلب أو التوصيل.
TXT;
    }
}
