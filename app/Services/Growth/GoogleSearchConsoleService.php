<?php

namespace App\Services\Growth;

use App\Models\BusinessProfile;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\SeoEntity;
use App\Services\MasterSpec\QuickAnswerGenerator;
use App\Services\Public\PublicDisplayNameResolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class GoogleSearchConsoleService
{
    /**
     * @return array{configured: bool, sites: list<string>, error: ?string}
     */
    public function testConnection(): array
    {
        $token = config('growth.google_search_console.access_token');

        if (! filled($token)) {
            return ['configured' => false, 'sites' => [], 'error' => 'GSC access token not configured'];
        }

        $response = Http::withToken($token)
            ->get('https://www.googleapis.com/webmasters/v3/sites');

        if (! $response->successful()) {
            return [
                'configured' => true,
                'sites' => [],
                'error' => $response->json('error.message') ?? $response->body(),
            ];
        }

        $sites = collect($response->json('siteEntry') ?? [])
            ->pluck('siteUrl')
            ->filter()
            ->values()
            ->all();

        return ['configured' => true, 'sites' => $sites, 'error' => null];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, error: ?string}
     */
    public function searchAnalytics(string $siteUrl, int $days = 28): array
    {
        $token = config('growth.google_search_console.access_token');

        if (! filled($token)) {
            return ['rows' => [], 'error' => 'GSC not configured'];
        }

        $start = now()->subDays($days)->toDateString();
        $end = now()->toDateString();

        $response = Http::withToken($token)
            ->post('https://searchconsole.googleapis.com/webmasters/v3/sites/'.urlencode($siteUrl).'/searchAnalytics/query', [
                'startDate' => $start,
                'endDate' => $end,
                'dimensions' => ['query', 'page'],
                'rowLimit' => 250,
            ]);

        if (! $response->successful()) {
            return ['rows' => [], 'error' => $response->json('error.message') ?? $response->body()];
        }

        return ['rows' => $response->json('rows') ?? [], 'error' => null];
    }
}
