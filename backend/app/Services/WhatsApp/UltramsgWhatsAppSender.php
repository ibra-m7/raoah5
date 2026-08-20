<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppSender;
use App\Exceptions\OtpException;
use App\Support\Phone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class UltramsgWhatsAppSender implements WhatsAppSender
{
    public function sendOtp(string $toE164, string $code): void
    {
        $instance = (string) config('whatsapp.ultramsg.instance_id');
        $token = (string) config('whatsapp.ultramsg.token');
        $base = rtrim((string) config('whatsapp.ultramsg.base_url'), '/');
        $from = Phone::companyNational() ?? (string) config('whatsapp.from_number');

        if ($instance === '' || $token === '') {
            throw new OtpException('إعدادات واتساب UltraMsg غير مكتملة.', 503);
        }

        $body = $this->message($code, $from);
        $url = "{$base}/{$instance}/messages/chat";

        $response = Http::asForm()
            ->timeout(20)
            ->post($url, [
                'token' => $token,
                'to' => '+'.$toE164,
                'body' => $body,
            ]);

        $json = $response->json();
        $sent = is_array($json) ? ($json['sent'] ?? null) : null;
        $hasError = is_array($json) && filled($json['error'] ?? null);

        if ($response->failed() || $hasError || $sent === 'false' || $sent === false) {
            Log::error('فشل إرسال واتساب عبر UltraMsg', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
                'to' => $toE164,
            ]);

            throw new OtpException('تعذّر إرسال رمز التحقق عبر واتساب. حاول لاحقاً.', 503);
        }
    }

    private function message(string $code, string $from): string
    {
        $lines = [
            "رمز التحقق لتطبيق روعة الخمسة هو: {$code}",
            'صالح لمدة 5 دقائق.',
            'لا تشارك هذا الرمز مع أي شخص.',
        ];

        if ($from !== '') {
            $lines[] = "مرسل من رقم الشركة: {$from}";
        }

        return implode("\n", $lines);
    }
}
