<?php

namespace App\Support;

final class Phone
{
    /** آخر 9 أرقام: يدخلان بدون OTP حتى على Render. */
    private const OTP_BYPASS_NATIONAL = [
        '778396448',
        '777234341',
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
        $bypass = self::matchBypass($digits);
        if ($bypass !== null) {
            return $bypass;
        }

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

        $bypass = self::matchBypass($e164);
        if ($bypass !== null) {
            return substr($bypass, -9);
        }

        return $e164;
    }

    private static function normalizeTestNumber(string $digits): ?string
    {
        return self::matchBypass($digits);
    }

    public static function display(string $e164): string
    {
        return '0'.self::national($e164);
    }

    public static function skipsOtp(?string $e164): bool
    {
        return $e164 !== null && self::matchBypass(self::digits($e164)) !== null;
    }

    /** @return string|null صيغة 967 + 9 أرقام */
    private static function matchBypass(string $digits): ?string
    {
        $digits = ltrim($digits, '+');
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        foreach (self::OTP_BYPASS_NATIONAL as $national) {
            if (
                $digits === $national
                || $digits === '0'.$national
                || $digits === '967'.$national
                || $digits === '966'.$national
                || str_ends_with($digits, $national)
            ) {
                return '967'.$national;
            }
        }

        return null;
    }
}
