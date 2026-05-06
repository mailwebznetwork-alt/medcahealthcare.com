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
    /** @var list<string> */
    public const RANGE_PRESETS = ['7d', '28d', '90d'];

    /**
     * @return array{
     *   fetched_at: string|null,
     *   meta: array{range_preset: string, range_days: int, date_range_label: string},
     *   summary: array{
     *     users: int,
     *     new_users: int,
     *     sessions: int,
     *     engaged_sessions: int,
     *     engagement_rate: float|null,
     *     avg_session_duration_sec: float|null,
     *     conversions: int,
     *     conversion_rate: float|null
     *   },
     *   sources: list<array{source: string, sessions: int}>,
     *   channels: list<array{channel: string, sessions: int}>,
     *   devices: list<array{device: string, sessions: int}>,
     *   countries: list<array{country: string, users: int}>,
     *   pages: list<array{path: string, title: string, views: int}>,
     *   events: list<array{name: string, count: int}>,
     *   error: string|null
     * }
     */
    public function fetchReportBundle(MarketingSetting $settings, string $rangePreset = '28d'): array
    {
        $propertyId = $this->resolveGa4PropertyId($settings);
        if ($propertyId === null || $propertyId === '') {
            return $this->emptyBundle('Set GA4 Property ID in Settings → Integrations (Google Services) for API reports. View reports under Growth Center → GA4.');
        }

        $preset = $this->normalizePreset($rangePreset);
        $cacheKey = $this->bundleCacheKey((string) $propertyId, $preset);
        $ttl = max(60, (int) config('marketing.ga4_cache_ttl', 3600));

        return Cache::remember($cacheKey, $ttl, function () use ($propertyId, $preset): array {
            $client = $this->makeClient();
            if ($client === null) {
                return $this->emptyBundle(
                    'Add a service account JSON path (MARKETING_GA4_CREDENTIALS_PATH or GOOGLE_APPLICATION_CREDENTIALS) with Analytics Data API access.'
                );
            }

            $property = 'properties/'.preg_replace('/\D/', '', (string) $propertyId);
            $range = $this->dateRangeForPreset($preset);

            try {
                $summaryRaw = $this->runSummary($client, $property, $range);
                $sources = $this->runTrafficSources($client, $property, $range);
                $channels = $this->runChannelGroups($client, $property, $range);
                $devices = $this->runDevices($client, $property, $range);
                $countries = $this->runCountries($client, $property, $range);
                $pages = $this->runTopPages($client, $property, $range);
                $events = $this->runEvents($client, $property, $range);

                $sessions = max(1, $summaryRaw['sessions']);
                $convRate = $summaryRaw['conversions'] > 0
                    ? round(($summaryRaw['conversions'] / $sessions) * 100, 2)
                    : null;

                $engagementPct = $summaryRaw['engagement_rate_ratio'] !== null
                    ? round((float) $summaryRaw['engagement_rate_ratio'] * 100, 2)
                    : null;

                return [
                    'fetched_at' => now()->toIso8601String(),
                    'meta' => [
                        'range_preset' => $preset,
                        'range_days' => $this->presetDays($preset),
                        'date_range_label' => $this->rangeLabel($preset),
                    ],
                    'summary' => [
                        'users' => $summaryRaw['users'],
                        'new_users' => $summaryRaw['new_users'],
                        'sessions' => $summaryRaw['sessions'],
                        'engaged_sessions' => $summaryRaw['engaged_sessions'],
                        'engagement_rate' => $engagementPct,
                        'avg_session_duration_sec' => $summaryRaw['avg_session_duration_sec'],
                        'conversions' => $summaryRaw['conversions'],
                        'conversion_rate' => $convRate,
                    ],
                    'sources' => $this->sortSessionsDesc($sources),
                    'channels' => $this->sortSessionsDesc($channels),
                    'devices' => $this->sortSessionsDesc($devices),
                    'countries' => $this->sortUsersDesc($countries),
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

        $id = (string) $propertyId;
        foreach (self::RANGE_PRESETS as $preset) {
            Cache::forget('marketing.ga4.bundle.'.sha1($id.'|'.$preset));
        }
    }

    private function bundleCacheKey(string $propertyId, string $preset): string
    {
        return 'marketing.ga4.bundle.'.sha1($propertyId.'|'.$preset);
    }

    private function normalizePreset(string $preset): string
    {
        $p = strtolower(trim($preset));

        return in_array($p, self::RANGE_PRESETS, true) ? $p : '28d';
    }

    private function presetDays(string $preset): int
    {
        return match ($preset) {
            '7d' => 7,
            '90d' => 90,
            default => 28,
        };
    }

    private function rangeLabel(string $preset): string
    {
        return match ($preset) {
            '7d' => __('Last 7 days'),
            '90d' => __('Last 90 days'),
            default => __('Last 28 days'),
        };
    }

    private function dateRangeForPreset(string $preset): DateRange
    {
        return match ($preset) {
            '7d' => (new DateRange)->setStartDate('7daysAgo')->setEndDate('today'),
            '90d' => (new DateRange)->setStartDate('90daysAgo')->setEndDate('today'),
            default => (new DateRange)->setStartDate('28daysAgo')->setEndDate('today'),
        };
    }

    /**
     * @return array{
     *   users: int,
     *   new_users: int,
     *   sessions: int,
     *   engaged_sessions: int,
     *   engagement_rate_ratio: float|null,
     *   avg_session_duration_sec: float|null,
     *   conversions: int
     * }
     */
    protected function runSummary(BetaAnalyticsDataClient $client, string $property, DateRange $range): array
    {
        $request = (new RunReportRequest)
            ->setProperty($property)
            ->setDateRanges([$range])
            ->setMetrics([
                (new Metric)->setName('activeUsers'),
                (new Metric)->setName('newUsers'),
                (new Metric)->setName('sessions'),
                (new Metric)->setName('engagedSessions'),
                (new Metric)->setName('engagementRate'),
                (new Metric)->setName('averageSessionDuration'),
                (new Metric)->setName('conversions'),
            ]);

        $response = $client->runReport($request);
        $rows = $response->getRows();
        if ($rows->count() === 0) {
            return [
                'users' => 0,
                'new_users' => 0,
                'sessions' => 0,
                'engaged_sessions' => 0,
                'engagement_rate_ratio' => null,
                'avg_session_duration_sec' => null,
                'conversions' => 0,
            ];
        }

        $m = $rows[0]->getMetricValues();

        $engRatio = isset($m[4]) ? (float) ($m[4]->getValue() ?? 0) : null;
        if ($engRatio !== null && ($engRatio < 0 || $engRatio > 1)) {
            $engRatio = max(0, min(1, $engRatio));
        }

        $avgSec = isset($m[5]) ? (float) ($m[5]->getValue() ?? 0) : null;

        return [
            'users' => (int) ($m[0]->getValue() ?? 0),
            'new_users' => (int) ($m[1]->getValue() ?? 0),
            'sessions' => (int) ($m[2]->getValue() ?? 0),
            'engaged_sessions' => (int) ($m[3]->getValue() ?? 0),
            'engagement_rate_ratio' => $engRatio,
            'avg_session_duration_sec' => $avgSec !== null ? round($avgSec, 2) : null,
            'conversions' => (int) ($m[6]->getValue() ?? 0),
        ];
    }

    /**
     * @return list<array{source: string, sessions: int}>
     */
    protected function runTrafficSources(BetaAnalyticsDataClient $client, string $property, DateRange $range): array
    {
        $request = (new RunReportRequest)
            ->setProperty($property)
            ->setDateRanges([$range])
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
     * @return list<array{channel: string, sessions: int}>
     */
    protected function runChannelGroups(BetaAnalyticsDataClient $client, string $property, DateRange $range): array
    {
        $request = (new RunReportRequest)
            ->setProperty($property)
            ->setDateRanges([$range])
            ->setDimensions([(new Dimension)->setName('sessionDefaultChannelGrouping')])
            ->setMetrics([(new Metric)->setName('sessions')])
            ->setLimit(14);

        $response = $client->runReport($request);
        $out = [];
        foreach ($response->getRows() as $row) {
            $out[] = [
                'channel' => (string) $row->getDimensionValues()[0]->getValue(),
                'sessions' => (int) $row->getMetricValues()[0]->getValue(),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{device: string, sessions: int}>
     */
    protected function runDevices(BetaAnalyticsDataClient $client, string $property, DateRange $range): array
    {
        $request = (new RunReportRequest)
            ->setProperty($property)
            ->setDateRanges([$range])
            ->setDimensions([(new Dimension)->setName('deviceCategory')])
            ->setMetrics([(new Metric)->setName('sessions')])
            ->setLimit(8);

        $response = $client->runReport($request);
        $out = [];
        foreach ($response->getRows() as $row) {
            $out[] = [
                'device' => (string) $row->getDimensionValues()[0]->getValue(),
                'sessions' => (int) $row->getMetricValues()[0]->getValue(),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{country: string, users: int}>
     */
    protected function runCountries(BetaAnalyticsDataClient $client, string $property, DateRange $range): array
    {
        $request = (new RunReportRequest)
            ->setProperty($property)
            ->setDateRanges([$range])
            ->setDimensions([(new Dimension)->setName('country')])
            ->setMetrics([(new Metric)->setName('activeUsers')])
            ->setLimit(15);

        $response = $client->runReport($request);
        $out = [];
        foreach ($response->getRows() as $row) {
            $out[] = [
                'country' => (string) $row->getDimensionValues()[0]->getValue(),
                'users' => (int) $row->getMetricValues()[0]->getValue(),
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function sortSessionsDesc(array $rows): array
    {
        usort($rows, fn ($a, $b) => ((int) ($b['sessions'] ?? 0)) <=> ((int) ($a['sessions'] ?? 0)));

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function sortUsersDesc(array $rows): array
    {
        usort($rows, fn ($a, $b) => ((int) ($b['users'] ?? 0)) <=> ((int) ($a['users'] ?? 0)));

        return $rows;
    }

    /**
     * @return list<array{path: string, title: string, views: int}>
     */
    protected function runTopPages(BetaAnalyticsDataClient $client, string $property, DateRange $range): array
    {
        $request = (new RunReportRequest)
            ->setProperty($property)
            ->setDateRanges([$range])
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
    protected function runEvents(BetaAnalyticsDataClient $client, string $property, DateRange $range): array
    {
        $request = (new RunReportRequest)
            ->setProperty($property)
            ->setDateRanges([$range])
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
     * @return array<string, mixed>
     */
    protected function emptyBundle(?string $error = null): array
    {
        return [
            'fetched_at' => null,
            'meta' => [
                'range_preset' => '28d',
                'range_days' => 28,
                'date_range_label' => __('Last 28 days'),
            ],
            'summary' => [
                'users' => 0,
                'new_users' => 0,
                'sessions' => 0,
                'engaged_sessions' => 0,
                'engagement_rate' => null,
                'avg_session_duration_sec' => null,
                'conversions' => 0,
                'conversion_rate' => null,
            ],
            'sources' => [],
            'channels' => [],
            'devices' => [],
            'countries' => [],
            'pages' => [],
            'events' => [],
            'error' => $error,
        ];
    }
}
