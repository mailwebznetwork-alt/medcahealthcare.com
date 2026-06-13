<?php

namespace App\Services\MasterSpec;

use App\Models\Service;
use Illuminate\Support\Str;

/**
 * Groups catalog services by shared keyword tokens for semantic / entity clustering.
 */
class SemanticKeywordClusterService
{
    /**
     * @return list<array{cluster: string, keywords: list<string>, services: list<string>, count: int}>
     */
    public function clusters(int $limit = 50): array
    {
        $tokenMap = [];

        Service::query()
            ->where('is_active', true)
            ->orderBy('service_code')
            ->each(function (Service $service) use (&$tokenMap): void {
                $tokens = $this->extractTokens($service);
                foreach ($tokens as $token) {
                    $tokenMap[$token] ??= [];
                    $tokenMap[$token][] = $service->service_code;
                }
            });

        $clusters = [];
        foreach ($tokenMap as $token => $codes) {
            if (count($codes) < 2) {
                continue;
            }
            $clusters[] = [
                'cluster' => $token,
                'keywords' => [$token],
                'services' => array_values(array_unique($codes)),
                'count' => count(array_unique($codes)),
            ];
        }

        usort($clusters, fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return array_slice($clusters, 0, $limit);
    }

    /**
     * @return list<string>
     */
    private function extractTokens(Service $service): array
    {
        $parts = array_merge(
            $service->target_keywords ?? [],
            $service->ai_keywords ?? [],
            explode(' ', (string) ($service->title ?? '')),
        );

        $tokens = [];
        foreach ($parts as $part) {
            $word = Str::lower(trim((string) $part));
            if (strlen($word) < 4 || in_array($word, ['home', 'care', 'medca', 'bangalore'], true)) {
                continue;
            }
            $tokens[] = $word;
        }

        return array_unique($tokens);
    }
}
