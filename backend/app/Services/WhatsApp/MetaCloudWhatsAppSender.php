<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppSender;
use App\Exceptions\OtpException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class MetaCloudWhatsAppSender implements WhatsAppSender
{
    public function sendOtp(string $toE164, string $code): void
    {
        $token = (string) config('whatsapp.meta.token');
        $phoneNumberId = (string) config('whatsapp.meta.phone_number_id');
        $template = (string) config('whatsapp.meta.template');
        $language = (string) config('whatsapp.meta.template_language');
        $version = (string) config('whatsapp.meta.graph_version');

        if ($token === '' || $phoneNumberId === '') {
            throw new OtpException('إعدادات واتساب Cloud API غير مكتملة.', 503);
        }

        $url = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $toE164,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => $language],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $code],
                        ],
                    ],
                    [
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => '0',
                        'parameters' => [
                            ['type' => 'text', 'text' => $code],
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->post($url, $payload);

        if ($response->failed()) {
            Log::error('فشل إرسال واتساب عبر Meta Cloud', [
                'status' => $response->status(),
                'body' => $response->json(),
                'to' => $toE164,
            ]);

            throw new OtpException('تعذّر إرسال رسالة واتساب. حاول لاحقاً.', 503);
        }
    }
}
