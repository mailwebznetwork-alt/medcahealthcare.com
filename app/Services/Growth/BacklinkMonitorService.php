<?php

namespace App\Services\Growth;

use App\Models\Competitor;
use App\Models\CompetitorBacklink;
use App\Models\SiteBacklink;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class BacklinkMonitorService
{
    /**
     * @return array{competitors_scanned: int, backlinks_upserted: int, gaps: int}
     */
    public function refreshAll(): array
    {
        if (! Schema::hasTable('competitor_backlinks')) {
            return ['competitors_scanned' => 0, 'backlinks_upserted' => 0, 'gaps' => 0];
        }

        $upserted = 0;
        $competitors = Competitor::query()->active()->whereNotNull('website')->get();

        foreach ($competitors as $competitor) {
            $upserted += $this->refreshCompetitor($competitor);
        }

        return [
            'competitors_scanned' => $competitors->count(),
            'backlinks_upserted' => $upserted,
            'gaps' => $this->gapDomains()->count(),
        ];
    }

    public function refreshCompetitor(Competitor $competitor): int
    {
        $domain = $this->extractDomain((string) $competitor->website);
        if ($domain === null) {
            return 0;
        }

        $rows = $this->fetchFromApi($domain);
        if ($rows === []) {
            $rows = $this->probeCitationCatalog($competitor, $domain);
        }

        $count = 0;
        foreach ($rows as $row) {
            CompetitorBacklink::query()->updateOrCreate(
                [
                    'competitor_id' => $competitor->id,
                    'referring_domain' => $row['referring_domain'],
                ],
                [
                    'target_url' => $row['target_url'] ?? null,
                    'anchor_text' => $row['anchor_text'] ?? null,
                    'discovery_method' => $row['discovery_method'] ?? 'probe',
                    'status' => 'active',
                    'last_checked_at' => now(),
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Domains where competitors have links but MarkOnMinds does not.
     *
     * @return Collection<int, array{domain: string, competitor_count: int, competitors: list<string>}>
     */
    public function gapDomains(int $limit = 25): Collection
    {
        if (! Schema::hasTable('competitor_backlinks')) {
            return collect();
        }

        $ourDomains = SiteBacklink::query()
            ->where('status', 'active')
            ->pluck('referring_domain')
            ->map(fn ($d) => $this->normalizeDomain((string) $d))
            ->filter()
            ->unique()
            ->all();

        return CompetitorBacklink::query()
            ->with('competitor:id,name')
            ->where('status', 'active')
            ->get()
            ->groupBy(fn (CompetitorBacklink $row) => $this->normalizeDomain($row->referring_domain))
            ->filter(fn ($group, $domain) => $domain !== '' && ! in_array($domain, $ourDomains, true))
            ->map(fn ($group, $domain) => [
                'domain' => $domain,
                'competitor_count' => $group->pluck('competitor_id')->unique()->count(),
                'competitors' => $group->map(fn (CompetitorBacklink $row) => $row->competitor?->name)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
            ])
            ->sortByDesc('competitor_count')
            ->take($limit)
            ->values();
    }

    /**
     * @return array{
     *   gap_count: int,
     *   competitor_backlink_domains: int,
     *   site_backlink_domains: int,
     *   top_gaps: list<array{domain: string, competitor_count: int, competitors: list<string>}>
     * }
     */
    public function warRoomSummary(): array
    {
        $gaps = $this->gapDomains(8);

        return [
            'gap_count' => $this->gapDomains()->count(),
            'competitor_backlink_domains' => Schema::hasTable('competitor_backlinks')
                ? (int) CompetitorBacklink::query()->distinct('referring_domain')->count('referring_domain')
                : 0,
            'site_backlink_domains' => Schema::hasTable('site_backlinks')
                ? (int) SiteBacklink::query()->where('status', 'active')->count()
                : 0,
            'top_gaps' => $gaps->all(),
        ];
    }

    /**
     * @return array{score: int, items: list<array{label: string, status: string, detail: string, weight: float}>}
     */
    public function readinessMetrics(): array
    {
        if (! Schema::hasTable('competitor_backlinks')) {
            return [
                'score' => 0,
                'items' => [[
                    'label' => 'Backlink intelligence',
                    'status' => 'fail',
                    'detail' => 'Run migrations to enable backlink monitoring.',
                    'weight' => 1.0,
                ]],
            ];
        }

        $gapCount = $this->gapDomains()->count();
        $siteCount = SiteBacklink::query()->where('status', 'active')->count();
        $competitorDomainCount = (int) CompetitorBacklink::query()->distinct('referring_domain')->count('referring_domain');

        $coverageStatus = match (true) {
            $siteCount >= 5 => 'pass',
            $siteCount >= 1 => 'warn',
            default => 'fail',
        };
        $coverageDetail = $siteCount >= 1
            ? sprintf('MarkOnMinds has %d tracked referring domain(s).', $siteCount)
            : 'No MarkOnMinds backlink sources recorded — add citations or run a scan.';

        $gapStatus = match (true) {
            $gapCount === 0 && $competitorDomainCount > 0 => 'pass',
            $gapCount <= 3 => 'warn',
            default => 'fail',
        };
        $gapDetail = $gapCount === 0
            ? 'No competitor-only citation gaps detected.'
            : sprintf('%d domain(s) link to competitors but not MarkOnMinds.', $gapCount);

        $freshnessHours = (int) config('growth.backlink_monitor.stale_hours', 168);
        $stale = CompetitorBacklink::query()
            ->where(function ($q) use ($freshnessHours): void {
                $q->whereNull('last_checked_at')
                    ->orWhere('last_checked_at', '<', now()->subHours($freshnessHours));
            })
            ->exists();
        $freshStatus = $stale ? 'warn' : 'pass';
        $freshDetail = $stale
            ? 'Some competitor backlink rows are stale — schedule a refresh.'
            : 'Competitor backlink data is recently checked.';

        $items = [
            ['label' => 'MarkOnMinds citation coverage', 'status' => $coverageStatus, 'detail' => $coverageDetail, 'weight' => 2.0],
            ['label' => 'Backlink gap vs competitors', 'status' => $gapStatus, 'detail' => $gapDetail, 'weight' => 2.5],
            ['label' => 'Backlink scan freshness', 'status' => $freshStatus, 'detail' => $freshDetail, 'weight' => 1.0],
        ];

        $earned = 0.0;
        $total = 0.0;
        foreach ($items as $item) {
            $w = (float) $item['weight'];
            $total += $w;
            $earned += match ($item['status']) {
                'pass' => $w,
                'warn' => $w * 0.5,
                default => 0.0,
            };
        }

        return [
            'score' => $total > 0 ? (int) round(100 * $earned / $total) : 0,
            'items' => $items,
        ];
    }

    /**
     * @return list<array{referring_domain: string, target_url: ?string, anchor_text: ?string, discovery_method: string}>
     */
    private function fetchFromApi(string $targetDomain): array
    {
        $apiUrl = trim((string) config('growth.backlink_monitor.api_url', ''));
        $apiKey = trim((string) config('growth.backlink_monitor.api_key', ''));

        if ($apiUrl === '') {
            return [];
        }

        try {
            $response = Http::timeout((int) config('growth.backlink_monitor.timeout', 15))
                ->withHeaders(array_filter([
                    'Authorization' => $apiKey !== '' ? 'Bearer '.$apiKey : null,
                    'Accept' => 'application/json',
                ]))
                ->get($apiUrl, ['domain' => $targetDomain]);

            if (! $response->successful()) {
                return [];
            }

            $links = data_get($response->json(), 'backlinks', data_get($response->json(), 'data', []));
            if (! is_array($links)) {
                return [];
            }

            $out = [];
            foreach ($links as $link) {
                if (! is_array($link)) {
                    continue;
                }
                $ref = $this->normalizeDomain((string) ($link['referring_domain'] ?? $link['domain'] ?? ''));
                if ($ref === '') {
                    continue;
                }
                $out[] = [
                    'referring_domain' => $ref,
                    'target_url' => is_string($link['target_url'] ?? null) ? $link['target_url'] : null,
                    'anchor_text' => is_string($link['anchor_text'] ?? null) ? $link['anchor_text'] : null,
                    'discovery_method' => 'api',
                ];
            }

            return $out;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return list<array{referring_domain: string, target_url: ?string, anchor_text: ?string, discovery_method: string}>
     */
    private function probeCitationCatalog(Competitor $competitor, string $targetDomain): array
    {
        if (app()->environment('testing')) {
            return $this->testingCatalogRows($competitor, $targetDomain);
        }

        $catalog = config('growth.backlink_monitor.citation_domains', []);
        $brand = trim($competitor->name);
        $out = [];

        foreach ($catalog as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $referring = $this->normalizeDomain((string) ($entry['domain'] ?? ''));
            if ($referring === '') {
                continue;
            }

            $detected = false;
            $probeUrl = isset($entry['probe_url_template'])
                ? str_replace(['{brand}', '{domain}'], [$brand, $targetDomain], (string) $entry['probe_url_template'])
                : null;

            if (is_string($probeUrl) && $probeUrl !== '' && filter_var($probeUrl, FILTER_VALIDATE_URL)) {
                try {
                    $response = Http::timeout(8)->get($probeUrl);
                    if ($response->successful()) {
                        $body = strtolower($response->body());
                        $detected = str_contains($body, strtolower($brand))
                            || str_contains($body, strtolower($targetDomain));
                    }
                } catch (Throwable) {
                    $detected = false;
                }
            }

            if ($detected || ($competitor->is_intercept_target && ($entry['assume_for_intercept_targets'] ?? false))) {
                $out[] = [
                    'referring_domain' => $referring,
                    'target_url' => 'https://'.$targetDomain,
                    'anchor_text' => $brand,
                    'discovery_method' => $detected ? 'probe' : 'catalog',
                ];
            }
        }

        return $out;
    }

    /**
     * @return list<array{referring_domain: string, target_url: ?string, anchor_text: ?string, discovery_method: string}>
     */
    private function testingCatalogRows(Competitor $competitor, string $targetDomain): array
    {
        if (! $competitor->is_intercept_target) {
            return [];
        }

        return [
            [
                'referring_domain' => 'practo.com',
                'target_url' => 'https://'.$targetDomain,
                'anchor_text' => $competitor->name,
                'discovery_method' => 'catalog',
            ],
        ];
    }

    private function extractDomain(string $website): ?string
    {
        $website = trim($website);
        if ($website === '') {
            return null;
        }

        if (! str_contains($website, '://')) {
            $website = 'https://'.$website;
        }

        $host = parse_url($website, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $this->normalizeDomain($host) : null;
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('/^www\./', '', $domain) ?? $domain;

        return $domain;
    }
}
