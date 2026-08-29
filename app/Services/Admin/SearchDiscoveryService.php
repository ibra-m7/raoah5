<?php

namespace App\Services\Admin;

use App\Models\SearchLog;
use Illuminate\Support\Collection;

class SearchDiscoveryService
{
    public const TRENDING_LIMIT = 8;

    public const AUTO_LOOKBACK_DAYS = 90;

    /**
     * @return list<string>
     */
    public function trendingTerms(int $limit = self::TRENDING_LIMIT): array
    {
        $pins = SearchTrendingPinService::activePhrases();
        $auto = $this->topQueriesFromLogs($limit * 3)
            ->pluck('query')
            ->all();

        return $this->mergeUniqueTerms(array_merge($pins, $auto), $limit);
    }

    /**
     * @return Collection<int, object{query: string, hits: int}>
     */
    public function topQueriesFromLogs(int $limit = 10): Collection
    {
        return SearchLog::query()
            ->select('query')
            ->selectRaw('COUNT(*) as hits')
            ->where('created_at', '>=', now()->subDays(self::AUTO_LOOKBACK_DAYS))
            ->groupBy('query')
            ->orderByDesc('hits')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  list<string>  $terms
     * @return list<string>
     */
    private function mergeUniqueTerms(array $terms, int $limit): array
    {
        $seen = [];
        $out = [];

        foreach ($terms as $term) {
            $text = trim((string) $term);
            if ($text === '' || mb_strlen($text) < 2 || mb_strlen($text) > 18) {
                continue;
            }

            $key = mb_strtolower($text);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $out[] = $text;

            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }
}
