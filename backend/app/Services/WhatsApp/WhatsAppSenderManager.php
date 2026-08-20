<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppSender;
use InvalidArgumentException;

final class WhatsAppSenderManager
{
    public function driver(?string $name = null): WhatsAppSender
    {
        $name ??= (string) config('whatsapp.driver', 'log');

        if ($name === 'log' && $this->ultramsgConfigured()) {
            $name = 'ultramsg';
        }

        return match ($name) {
            'log' => new LogWhatsAppSender,
            'ultramsg' => new UltramsgWhatsAppSender,
            'meta' => new MetaCloudWhatsAppSender,
            default => throw new InvalidArgumentException("قناة واتساب غير معروفة: {$name}"),
        };
    }

    private function ultramsgConfigured(): bool
    {
        return filled(config('whatsapp.ultramsg.instance_id'))
            && filled(config('whatsapp.ultramsg.token'));
    }
}
