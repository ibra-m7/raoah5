<?php

namespace App\Services\Ai;

use App\Support\AiSettings;
use Illuminate\Http\Client\ConnectionException;
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
        $apiKey = trim((string) config('services.gemini.key'));
        if ($apiKey === '') {
            throw new RuntimeException('missing_key');
        }

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

        $lastError = '';

        foreach ($this->modelsToTry() as $model) {
            foreach ($this->payloads($system, $contents) as $payload) {
                try {
                    $response = Http::timeout(45)
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
            }
        }

        throw new RuntimeException($lastError !== '' ? $lastError : 'gemini_failed');
    }

    /**
     * @param  list<array<string, mixed>>  $contents
     * @return list<array<string, mixed>>
     */
    private function payloads(string $system, array $contents): array
    {
        $base = [
            'systemInstruction' => [
                'parts' => [['text' => $system]],
            ],
            'contents' => $contents,
        ];

        return [
            $base + [
                'generationConfig' => [
                    'maxOutputTokens' => 4096,
                    'responseMimeType' => 'application/json',
                    'thinkingConfig' => [
                        'thinkingBudget' => 0,
                    ],
                ],
            ],
            $base + [
                'generationConfig' => [
                    'maxOutputTokens' => 4096,
                    'responseMimeType' => 'application/json',
                    'thinkingConfig' => [
                        'thinkingLevel' => 'minimal',
                    ],
                ],
            ],
            $base + [
                'generationConfig' => [
                    'maxOutputTokens' => 4096,
                    'responseMimeType' => 'application/json',
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function modelsToTry(): array
    {
        return array_values(array_unique([
            AiSettings::model(),
            ...AiSettings::fallbacks(),
            'gemini-2.5-flash',
            'gemini-2.0-flash',
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
