<?php

namespace App\Support;

final class Phone
{
    /** رقم يمني مسموح للاختبار فقط — صيغة دولية بدون +. */
    private const TEST_E164 = [
        '967778396448',
        '967777234341',
    ];

    public static function countryCode(): string
    {
        return (string) config('whatsapp.country_code', '966');
    }

    /**
     * يحوّل المدخل إلى أرقام لاتينية فقط.
     */
    public static function digits(string $raw): string
    {
        $mapped = strtr($raw, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);

        return (string) preg_replace('/\D+/', '', $mapped);
    }

    /**
     * يعيد الرقم بصيغة دولية بدون + (مثال: 967778369448) أو null إن كان غير صالح.
     */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $digits = self::digits($raw);
        $cc = self::countryCode();

        if (str_starts_with($digits, '00'.$cc)) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, $cc) && strlen($digits) === strlen($cc) + 9) {
            $national = substr($digits, strlen($cc));

            return self::isValidNational($national) ? $digits : null;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = substr($digits, 1);
        }

        if (self::isValidNational($digits)) {
            return $cc.$digits;
        }

        return self::normalizeTestNumber($digits);
    }

    public static function companyE164(): ?string
    {
        return self::normalize((string) config('whatsapp.from_number'));
    }

    public static function companyNational(): ?string
    {
        $e164 = self::companyE164();

        return $e164 ? self::national($e164) : null;
    }

    public static function isValidNational(string $national): bool
    {
        return (bool) preg_match('/^5\d{8}$/', $national);
    }

    public static function national(string $e164): string
    {
        $cc = self::countryCode();
        if (str_starts_with($e164, $cc)) {
            return substr($e164, strlen($cc));
        }

        foreach (self::TEST_E164 as $allowed) {
            if ($e164 === $allowed && strlen($allowed) > 9) {
                return substr($allowed, -9);
            }
        }

        return $e164;
    }

    private static function normalizeTestNumber(string $digits): ?string
    {
        foreach (self::TEST_E164 as $allowed) {
            $national = substr($allowed, -9);
            $variants = [
                $allowed,
                '00'.$allowed,
                $national,
                '0'.$national,
            ];
            if (in_array($digits, $variants, true)) {
                return $allowed;
            }
        }

        return null;
    }

    public static function display(string $e164): string
    {
        return '0'.self::national($e164);
    }
}
