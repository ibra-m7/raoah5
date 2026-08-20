<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmClient
{
    /**
     * @param  list<string>  $tokens
     * @param  array<string, string>  $data
     * @return list<string> Invalid tokens that should be removed
     */
    public function send(array $tokens, string $title, string $body, array $data = []): array
    {
        $tokens = array_values(array_unique(array_filter($tokens)));
        if ($tokens === [] || ! $this->isConfigured()) {
            return [];
        }

        $accessToken = $this->accessToken();
        if ($accessToken === null) {
            return [];
        }

        $invalid = [];
        $url = sprintf(
            'https://fcm.googleapis.com/v1/projects/%s/messages:send',
            rawurlencode($this->projectId())
        );

        foreach (array_chunk($tokens, 20) as $chunk) {
            $list = array_values($chunk);
            $responses = Http::pool(function ($pool) use ($list, $url, $accessToken, $title, $body, $data) {
                foreach ($list as $index => $token) {
                    $pool->as((string) $index)
                        ->withToken($accessToken)
                        ->acceptJson()
                        ->asJson()
                        ->timeout(20)
                        ->post($url, [
                            'message' => [
                                'token' => $token,
                                'notification' => [
                                    'title' => $title,
                                    'body' => $body,
                                ],
                                'data' => $this->stringify(array_merge($data, [
                                    'title' => $title,
                                    'body' => $body,
                                ])),
                                'android' => [
                                    'priority' => 'HIGH',
                                    'ttl' => '86400s',
                                    'notification' => [
                                        'channel_id' => 'raoah_default',
                                        'sound' => 'default',
                                        'default_sound' => true,
                                        'default_vibrate_timings' => true,
                                        'notification_priority' => 'PRIORITY_MAX',
                                        'visibility' => 'PUBLIC',
                                    ],
                                ],
                                'apns' => [
                                    'headers' => [
                                        'apns-priority' => '10',
                                    ],
                                    'payload' => [
                                        'aps' => [
                                            'sound' => 'default',
                                            'badge' => 1,
                                            'alert' => [
                                                'title' => $title,
                                                'body' => $body,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ]);
                }
            });

            foreach ($list as $index => $token) {
                $response = $responses[(string) $index] ?? null;
                if ($response === null || $response->successful()) {
                    continue;
                }

                $status = (string) data_get($response->json(), 'error.status', '');
                $code = $response->status();
                if ($code === 404 || in_array($status, ['NOT_FOUND', 'UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
                    $invalid[] = $token;
                } else {
                    Log::warning('fcm.send_failed', [
                        'http' => $code,
                        'status' => $status,
                        'message' => data_get($response->json(), 'error.message'),
                    ]);
                }
            }
        }

        return array_values(array_unique($invalid));
    }

    public function isConfigured(): bool
    {
        return $this->credentials() !== null && $this->projectId() !== '';
    }

    public function projectId(): string
    {
        $fromFile = (string) ($this->credentials()['project_id'] ?? '');
        if ($fromFile !== '') {
            return $fromFile;
        }

        return trim((string) config('services.fcm.project_id'));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function credentials(): ?array
    {
        $configured = trim((string) config('services.fcm.credentials'));
        $candidates = [
            $configured,
            $configured !== '' ? base_path($configured) : '',
            storage_path('app/firebase-service-account.json'),
        ];

        foreach ($candidates as $path) {
            if ($path === '' || ! is_file($path)) {
                continue;
            }

            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded) && isset($decoded['private_key'], $decoded['client_email'])) {
                return $decoded;
            }
        }

        return null;
    }

    private function accessToken(): ?string
    {
        $cached = Cache::get('fcm_access_token');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $credentials = $this->credentials();
        if ($credentials === null) {
            return null;
        }

        $now = time();
        $header = $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $this->b64(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3500,
        ]));

        $unsigned = $header.'.'.$claims;
        $key = openssl_pkey_get_private((string) $credentials['private_key']);
        if ($key === false) {
            Log::error('fcm.invalid_private_key');

            return null;
        }

        $signature = '';
        openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256);
        $jwt = $unsigned.'.'.$this->b64($signature);

        $response = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        $token = $response->json('access_token');
        if (! is_string($token) || $token === '') {
            Log::warning('fcm.token_failed', ['http' => $response->status()]);

            return null;
        }

        Cache::put('fcm_access_token', $token, now()->addMinutes(50));

        return $token;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function stringify(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }
            $out[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        return $out;
    }

    private function b64(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
