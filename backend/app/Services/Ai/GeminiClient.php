<?php

namespace App\Services\Ai;

use App\Support\AiSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

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

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $system]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.55,
                'maxOutputTokens' => 1024,
                'responseMimeType' => 'application/json',
            ],
        ];

        $lastStatus = 0;
        $lastError = '';

        foreach ($this->modelsToTry() as $model) {
            $response = Http::timeout(35)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->acceptJson()
                ->asJson()
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent',
                    $payload
                );

            if ($response->successful()) {
                $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
                if (is_string($text) && trim($text) !== '') {
                    return $text;
                }

                $lastError = 'empty_reply';
                continue;
            }

            $lastStatus = $response->status();
            $lastError = (string) data_get($response->json(), 'error.message', 'gemini_http_'.$lastStatus);

            Log::warning('gemini.failed', [
                'status' => $lastStatus,
                'model' => $model,
                'error' => mb_substr($lastError, 0, 180),
            ]);

            if (! in_array($lastStatus, [404, 400], true)) {
                break;
            }
        }

        throw new RuntimeException($lastError !== '' ? $lastError : 'gemini_http_'.$lastStatus);
    }

    /**
     * @return list<string>
     */
    private function modelsToTry(): array
    {
        return array_values(array_unique([
            AiSettings::model(),
            ...AiSettings::fallbacks(),
        ]));
    }
}
