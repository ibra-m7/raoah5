<?php

namespace App\Contracts;

interface WhatsAppSender
{
    /**
     * يرسل رمز التحقق إلى رقم دولي بدون + (مثال: 967778369448).
     */
    public function sendOtp(string $toE164, string $code): void;
}
