<?php

namespace App\Services\Ai;

use App\Support\AiSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GeminiClient
{
    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    public function generateJson(string $system, string $userPrompt, array $history = []): string
    {
        return $this->requestJson($system, $this->buildContents($userPrompt, $history), fast: false);
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    public function generateJsonFast(string $system, string $userPrompt, array $history = []): string
    {
        return $this->requestJson($system, $this->buildContents($userPrompt, $history), fast: true);
    }

    /**
     * Concurrent fast JSON generation. Same model/payload as generateJsonFast.
     *
     * @param  array<string|int, array{system: string, user: string}>  $requests
     * @return array<string|int, string>
     */
    public function generateJsonFastMany(array $requests): array
    {
        if ($requests === []) {
            return [];
        }

        $apiKey = trim((string) config('services.gemini.key'));
        if ($apiKey === '') {
            throw new RuntimeException('missing_key');
        }

        $model = AiSettings::model();
        $timeout = 20;
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent';

        $responses = Http::pool(function (Pool $pool) use ($requests, $apiKey, $url, $timeout) {
            foreach ($requests as $key => $request) {
                $system = (string) ($request['system'] ?? '');
                $user = (string) ($request['user'] ?? '');
                $payload = $this->fastPayload($system, $this->buildContents($user, []));

                $pool->as((string) $key)
                    ->timeout($timeout)
                    ->withHeaders(['x-goog-api-key' => $apiKey])
                    ->acceptJson()
                    ->asJson()
                    ->post($url, $payload);
            }
        });

        $results = [];
        foreach ($requests as $key => $request) {
            $response = $responses[(string) $key] ?? null;
            if (! $response instanceof Response || ! $response->successful()) {
                continue;
            }

            $text = $this->extractText($response->json());
            if ($text !== '') {
                $results[$key] = $text;
            }
        }

        return $results;
    }

    /**
     * @param  list<array<string, mixed>>  $contents
     * @return array<string, mixed>
     */
    private function fastPayload(string $system, array $contents): array
    {
        return [
            'systemInstruction' => [
                'parts' => [['text' => $system]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'maxOutputTokens' => 1536,
                'responseMimeType' => 'application/json',
                'thinkingConfig' => [
                    'thinkingBudget' => 0,
                ],
            ],
        ];
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return list<array<string, mixed>>
     */
    private function buildContents(string $userPrompt, array $history): array
    {
        $contents = [];
        foreach ($history as $turn) {
            $contents[] = [
                'role' => ($turn['role'] ?? '') === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => (string) ($turn['content'] ?? '')]],
            ];
        }
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $userPrompt]],
        ];

        return $contents;
    }

    /**
     * @param  list<array<string, mixed>>  $contents
     */
    private function requestJson(string $system, array $contents, bool $fast): string
    {
        $apiKey = trim((string) config('services.gemini.key'));
        if ($apiKey === '') {
            throw new RuntimeException('missing_key');
        }

        $lastError = '';
        $models = $this->modelsToTry($fast);
        $timeout = $fast ? 20 : 45;

        foreach ($models as $model) {
            foreach ($this->payloads($system, $contents, $fast) as $payload) {
                try {
                    $response = Http::timeout($timeout)
                        ->withHeaders(['x-goog-api-key' => $apiKey])
                        ->acceptJson()
                        ->asJson()
                        ->post(
                            'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent',
                            $payload
                        );
                } catch (ConnectionException $e) {
                    $lastError = 'connection';
                    Log::warning('gemini.failed', [
                        'model' => $model,
                        'error' => 'connection',
                        'detail' => mb_substr($e->getMessage(), 0, 180),
                    ]);
                    continue;
                } catch (Throwable $e) {
                    $lastError = $e->getMessage() !== '' ? $e->getMessage() : 'request_failed';
                    Log::warning('gemini.failed', [
                        'model' => $model,
                        'error' => mb_substr($lastError, 0, 180),
                    ]);
                    continue;
                }

                if ($response->successful()) {
                    $text = $this->extractText($response->json());
                    if ($text !== '') {
                        return $text;
                    }

                    $lastError = 'empty_reply';
                    Log::warning('gemini.failed', [
                        'model' => $model,
                        'error' => 'empty_reply',
                        'finish' => data_get($response->json(), 'candidates.0.finishReason'),
                    ]);
                    continue;
                }

                $status = $response->status();
                $lastError = (string) data_get($response->json(), 'error.message', 'gemini_http_'.$status);

                Log::warning('gemini.failed', [
                    'status' => $status,
                    'model' => $model,
                    'error' => mb_substr($lastError, 0, 180),
                ]);

                if (in_array($status, [404, 429], true)) {
                    break;
                }
            }
        }

        throw new RuntimeException($lastError !== '' ? $lastError : 'gemini_failed');
    }

    /**
     * @param  list<array<string, mixed>>  $contents
     * @return list<array<string, mixed>>
     */
    private function payloads(string $system, array $contents, bool $fast = false): array
    {
        $base = [
            'systemInstruction' => [
                'parts' => [['text' => $system]],
            ],
            'contents' => $contents,
        ];

        $plain = $base + [
            'generationConfig' => [
                'maxOutputTokens' => 4096,
                'responseMimeType' => 'application/json',
            ],
        ];

        $primary = $base + [
            'generationConfig' => [
                'maxOutputTokens' => 4096,
                'responseMimeType' => 'application/json',
                'thinkingConfig' => [
                    'thinkingBudget' => 0,
                ],
            ],
        ];

        if ($fast) {
            return [$this->fastPayload($system, $contents)];
        }

        return [
            $plain,
            $primary,
            $base + [
                'generationConfig' => [
                    'maxOutputTokens' => 4096,
                    'responseMimeType' => 'application/json',
                    'thinkingConfig' => [
                        'thinkingLevel' => 'minimal',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function modelsToTry(bool $fast = false): array
    {
        if ($fast) {
            $fallbacks = AiSettings::fallbacks();

            return array_values(array_unique([
                AiSettings::model(),
                $fallbacks[0] ?? 'gemini-flash-lite-latest',
            ]));
        }

        return array_values(array_unique([
            AiSettings::model(),
            ...AiSettings::fallbacks(),
            'gemini-flash-lite-latest',
        ]));
    }

    private function extractText(mixed $json): string
    {
        $parts = data_get($json, 'candidates.0.content.parts', []);
        if (! is_array($parts)) {
            return '';
        }

        $chunks = [];
        foreach ($parts as $part) {
            if (! is_array($part)) {
                continue;
            }
            if (! empty($part['thought'])) {
                continue;
            }
            $text = trim((string) ($part['text'] ?? ''));
            if ($text !== '') {
                $chunks[] = $text;
            }
        }

        return trim(implode("\n", $chunks));
    }
}
