<?php

namespace App\Services\Ai;

class ProductNameParser
{
    /**
     * @return array{
     *     weight_label: string,
     *     piece_count: int|null,
     *     quantity_label: string,
     *     search_tokens: list<string>
     * }
     */
    public function parse(string $name): array
    {
        $name = trim($name);

        return [
            'weight_label' => $this->weightLabel($name),
            'piece_count' => $this->pieceCount($name),
            'quantity_label' => $this->quantityLabel($name),
            'search_tokens' => $this->tokens($name),
        ];
    }

    private function weightLabel(string $name): string
    {
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(كيلو|كجم|كغ|kg|جرام|غرام|جم|g|مل|ml|لتر|ل|l)\b/ui', $name, $match) === 1) {
            return $this->normalizeUnit($match[1], $match[2]);
        }

        return '';
    }

    private function pieceCount(string $name): ?int
    {
        if (preg_match('/(?:×|x)\s*(\d{1,3})\b/ui', $name, $match) === 1) {
            return $this->boundedInt((int) $match[1]);
        }

        if (preg_match('/(\d{1,3})\s*(?:حبة|حبات|قطعة|قطع|علبة|عبوة|كيس|أكياس)\b/u', $name, $match) === 1) {
            return $this->boundedInt((int) $match[1]);
        }

        return null;
    }

    private function quantityLabel(string $name): string
    {
        $pieces = $this->pieceCount($name);
        if ($pieces !== null && $pieces > 1) {
            return 'العدد '.$pieces;
        }

        $weight = $this->weightLabel($name);
        if ($weight !== '') {
            return $weight;
        }

        return '';
    }

    /**
     * @return list<string>
     */
    private function tokens(string $name): array
    {
        $normalized = mb_strtolower($name);
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalized) ?? $normalized;
        $parts = preg_split('/\s+/u', trim((string) $normalized)) ?: [];

        $stop = ['و', 'في', 'من', 'مع', 'على', 'ال', 'لل', 'ب'];
        $tokens = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if (mb_strlen($part) < 2 || in_array($part, $stop, true)) {
                continue;
            }
            $tokens[] = $part;
        }

        return array_values(array_unique($tokens));
    }

    private function normalizeUnit(string $amount, string $unit): string
    {
        $amount = str_replace(',', '.', $amount);
        $unit = mb_strtolower(trim($unit));

        return match (true) {
            str_contains($unit, 'كيلو') || in_array($unit, ['كجم', 'كغ', 'kg'], true) => $amount.' كجم',
            str_contains($unit, 'جرام') || str_contains($unit, 'غرام') || in_array($unit, ['جم', 'g'], true) => $amount.' جم',
            str_contains($unit, 'مل') || $unit === 'ml' => $amount.' مل',
            str_contains($unit, 'لتر') || in_array($unit, ['ل', 'l'], true) => $amount.' لتر',
            default => trim($amount.' '.$unit),
        };
    }

    private function boundedInt(int $value): ?int
    {
        return $value >= 2 && $value <= 9999 ? $value : null;
    }
}
