<?php

namespace App\Services\Marketing;

use App\Models\Integration;
use App\Models\MarketingSetting;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class Ga4DataApiService
{
    /**
     * @return array{
     *   fetched_at: string|null,
     *   summary: array{users: int, sessions: int, conversions: int, conversion_rate: float|null},
     *   sources: list<array{source: string, sessions: int}>,
     *   pages: list<array{path: string, title: string, views: int}>,
     *   events: list<array{name: string, count: int}>,
     *   error: string|null
     * }
     */
    public function fetchReportBundle(MarketingSetting $settings): array
    {
        $propertyId = $this->resolveGa4PropertyId($settings);
        if ($propertyId === null || $propertyId === '') {
            return $this->emptyBundle('Set GA4 Property ID in Settings → Integrations (Google Services) for API reports. View reports under Growth Center → GA4.');
        }

        $cacheKey = 'marketing.ga4.bundle.'.sha1((string) $propertyId);
        $ttl = max(60, (int) config('marketing.ga4_cache_ttl', 3600));

        return Cache::remember($cacheKey, $ttl, function () use ($propertyId): array {
            $client = $this->makeClient();
            if ($client === null) {
                return $this->emptyBundle(
                    'Add a service account JSON path (MARKETING_GA4_CREDENTIALS_PATH or GOOGLE_APPLICATION_CREDENTIALS) with Analytics Data API access.'
                );
            }

            $property = 'properties/'.preg_replace('/\D/', '', (string) $propertyId);

            try {
                $summary = $this->runSummary($client, $property);
                $sources = $this->runTrafficSources($client, $property);
                $pages = $this->runTopPages($client, $property);
                $events = $this->runEvents($client, $property);

                $sessions = max(1, $summary['sessions']);
                $convRate = $summary['conversions'] > 0
                    ? round(($summary['conversions'] / $sessions) * 100, 2)
                    : null;

                return [
                    'fetched_at' => now()->toIso8601String(),
                    'summary' => [
                        'users' => $summary['users'],
                        'sessions' => $summary['sessions'],
                        'conversions' => $summary['conversions'],
                        'conversion_rate' => $convRate,
                    ],
                    'sources' => $sources,
                    'pages' => $pages,
                    'events' => $events,
                    'error' => null,
                ];
            } catch (Throwable $e) {
                Log::warning('GA4 Data API report failed', ['message' => $e->getMessage()]);

                return $this->emptyBundle($e->getMessage());
            }
        });
    }

    public static function forgetCache(MarketingSetting $settings): void
    {
        $propertyId = $settings->ga4_property_id;
        if (Schema::hasTable('integrations')) {
            $google = Integration::query()->where('name', 'google_services')->first();
            $credentials = $google?->credentials ?? [];
            $propertyId = $credentials['property_id'] ?? $propertyId;
        }
        if ($propertyId === null || $propertyId === '') {
            return;
        }

        Cache::forget('marketing.ga4.bundle.'.sha1((string) $propertyId));
    }

    private function resolveGa4PropertyId(MarketingSetting $settings): ?string
    {
        $propertyId = $settings->ga4_property_id;

        if (Schema::hasTable('integrations')) {
            $google = Integration::query()->where('name', 'google_services')->first();
            $credentials = $google?->credentials ?? [];
            $integrationPropertyId = $credentials['property_id'] ?? null;
            if (is_string($integrationPropertyId) && $integrationPropertyId !== '') {
                $propertyId = $integrationPropertyId;
            }
        }

        return $propertyId;
    }

    /**
     * @return array{users: int, sessions: int, conversions: int}
     */
    protected function runSummary(BetaAnalyticsDataClient $client, string $property): array
    {
        $request = (new RunReportRequest)
            ->setProperty($property)
            ->setDateRanges([
                (new DateRange)->setStartDate('28daysAgo')->setEndDate('today'),
            ])
            ->setMetrics([
                (new Metric)->setName('activeUsers'),
                (new Metric)->setName('sessions'),
                (new Metric)->setName('conversions'),
            ]);

        $response = $client->runReport($request);
        $rows = $response->getRows();
        if ($rows->count() === 0) {
            return ['users' => 0, 'sessions' => 0, 'conversions' => 0];
        }

        $m = $rows[0]->getMetricValues();

        return [
            'users' => (int) ($m[0]->getValue() ?? 0),
            'sessions' => (int) ($m[1]->getValue() ?? 0),
            'conversions' => (int) ($m[2]->getValue() ?? 0),
        ];
    }

    /**
     * @return list<array{source: string, sessions: int}>
     */
    protected function runTrafficSources(BetaAnalyticsDataClient $client, string $property): array
    {
        $request = (new RunReportRequest)
            ->setProperty($property)
            ->setDateRanges([
                (new DateRange)->setStartDate('28daysAgo')->setEndDate('today'),
            ])
            ->setDimensions([(new Dimension)->setName('sessionSource')])
            ->setMetrics([(new Metric)->setName('sessions')])
            ->setLimit(15);

        $response = $client->runReport($request);
        $out = [];
        foreach ($response->getRows() as $row) {
            $out[] = [
                'source' => (string) $row->getDimensionValues()[0]->getValue(),
                'sessions' => (int) $row->getMetricValues()[0]->getValue(),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{path: string, title: string, views: int}>
     */
    protected function runTopPages(BetaAnalyticsDataClient $client, string $property): array
    {
        $request = (new RunReportRequest)
            ->setProperty($property)
            ->setDateRanges([
                (new DateRange)->setStartDate('28daysAgo')->setEndDate('today'),
            ])
            ->setDimensions([
                (new Dimension)->setName('pagePath'),
                (new Dimension)->setName('pageTitle'),
            ])
            ->setMetrics([(new Metric)->setName('screenPageViews')])
            ->setLimit(15);

        $response = $client->runReport($request);
        $out = [];
        foreach ($response->getRows() as $row) {
            $dims = $row->getDimensionValues();
            $out[] = [
                'path' => (string) $dims[0]->getValue(),
                'title' => (string) $dims[1]->getValue(),
                'views' => (int) $row->getMetricValues()[0]->getValue(),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{name: string, count: int}>
     */
    protected function runEvents(BetaAnalyticsDataClient $client, string $property): array
    {
        $request = (new RunReportRequest)
            ->setProperty($property)
            ->setDateRanges([
                (new DateRange)->setStartDate('28daysAgo')->setEndDate('today'),
            ])
            ->setDimensions([(new Dimension)->setName('eventName')])
            ->setMetrics([(new Metric)->setName('eventCount')])
            ->setLimit(25);

        $response = $client->runReport($request);
        $out = [];
        foreach ($response->getRows() as $row) {
            $out[] = [
                'name' => (string) $row->getDimensionValues()[0]->getValue(),
                'count' => (int) $row->getMetricValues()[0]->getValue(),
            ];
        }

        return $out;
    }

    protected function makeClient(): ?BetaAnalyticsDataClient
    {
        $path = config('marketing.ga4_credentials_path') ?: env('GOOGLE_APPLICATION_CREDENTIALS');
        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            return null;
        }

        return new BetaAnalyticsDataClient([
            'credentials' => $path,
        ]);
    }

    /**
     * @return array{
     *   fetched_at: null,
     *   summary: array{users: int, sessions: int, conversions: int, conversion_rate: null},
     *   sources: list<array{source: string, sessions: int}>,
     *   pages: list<array{path: string, title: string, views: int}>,
     *   events: list<array{name: string, count: int}>,
     *   error: string|null
     * }
     */
    protected function emptyBundle(?string $error = null): array
    {
        return [
            'fetched_at' => null,
            'summary' => [
                'users' => 0,
                'sessions' => 0,
                'conversions' => 0,
                'conversion_rate' => null,
            ],
            'sources' => [],
            'pages' => [],
            'events' => [],
            'error' => $error,
        ];
    }
}
