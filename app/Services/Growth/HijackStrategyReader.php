<?php

namespace App\Services\Growth;

use App\Models\SiteKeywordRanking;
use App\Services\Growth\SeoEntityResolver;
use Illuminate\Support\Facades\Schema;

class HijackStrategyReader
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function allStrategies(): array
    {
        if (! Schema::hasTable('seo_entities') || ! Schema::hasColumn('seo_entities', 'hijack_strategy')) {
            return [];
        }

        $entity = app(SeoEntityResolver::class)->forCurrentBusiness();

        return $entity?->hijackStrategies() ?? [];
    }

    /**
     * Strategies whose target keyword overlaps page focus keywords (case-insensitive).
     *
     * @param  list<string>  $focusKeywords
     * @return list<array{key: string, strategy: array<string, mixed>}>
     */
    public function forPageKeywords(array $focusKeywords): array
    {
        $normalizedFocus = collect($focusKeywords)
            ->filter(fn ($kw) => is_string($kw) && trim($kw) !== '')
            ->map(fn (string $kw) => SiteKeywordRanking::normalizeKeyword($kw))
            ->unique()
            ->values()
            ->all();

        if ($normalizedFocus === []) {
            return [];
        }

        $matches = [];
        foreach ($this->allStrategies() as $key => $strategy) {
            if (! is_array($strategy)) {
                continue;
            }

            $target = SiteKeywordRanking::normalizeKeyword((string) ($strategy['keyword'] ?? ''));
            if ($target === '') {
                continue;
            }

            foreach ($normalizedFocus as $focus) {
                if ($focus === $target || str_contains($focus, $target) || str_contains($target, $focus)) {
                    $matches[] = [
                        'key' => (string) $key,
                        'strategy' => $strategy,
                    ];

                    break;
                }
            }
        }

        usort($matches, fn (array $a, array $b): int => ((int) ($b['strategy']['hijack_priority'] ?? 0))
            <=> ((int) ($a['strategy']['hijack_priority'] ?? 0)));

        return $matches;
    }
}
