<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppSender;
use App\Support\Phone;
use Illuminate\Support\Facades\Log;

final class LogWhatsAppSender implements WhatsAppSender
{
    public function sendOtp(string $toE164, string $code): void
    {
        $from = Phone::companyE164() ?: 'غير محدد';

        Log::info('واتساب OTP (وضع السجل)', [
            'from' => $from,
            'to' => $toE164,
            'code' => $code,
        ]);
    }
}
