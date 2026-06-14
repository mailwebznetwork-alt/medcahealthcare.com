<?php

namespace App\Services\Growth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSearchConsoleService
{
    /**
     * @return array{configured: bool, sites: list<string>, error: ?string, auth_mode: ?string}
     */
    public function testConnection(): array
    {
        $token = $this->resolveAccessToken();

        if ($token === null) {
            return [
                'configured' => false,
                'sites' => [],
                'error' => 'GSC credentials not configured (set MEDCA_GSC_ACCESS_TOKEN or OAuth refresh token)',
                'auth_mode' => null,
            ];
        }

        $response = Http::withToken($token)
            ->get('https://www.googleapis.com/webmasters/v3/sites');

        if (! $response->successful()) {
            return [
                'configured' => true,
                'sites' => [],
                'error' => $response->json('error.message') ?? $response->body(),
                'auth_mode' => $this->authMode(),
            ];
        }

        $sites = collect($response->json('siteEntry') ?? [])
            ->pluck('siteUrl')
            ->filter()
            ->values()
            ->all();

        return [
            'configured' => true,
            'sites' => $sites,
            'error' => null,
            'auth_mode' => $this->authMode(),
        ];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, error: ?string}
     */
    public function searchAnalytics(string $siteUrl, int $days = 28): array
    {
        $token = $this->resolveAccessToken();

        if ($token === null) {
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

    private function resolveAccessToken(): ?string
    {
        if ($this->oauthConfigured()) {
            $cached = Cache::get('growth.gsc.oauth_access_token');
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }

            $token = $this->fetchOAuthAccessToken();
            if ($token !== null) {
                Cache::put('growth.gsc.oauth_access_token', $token, now()->addSeconds(3500));
            }

            return $token;
        }

        $static = config('growth.google_search_console.access_token');

        return filled($static) ? (string) $static : null;
    }

    private function oauthConfigured(): bool
    {
        $store = app(GoogleSearchConsoleCredentialStore::class);

        foreach ([$store->clientId(), $store->clientSecret(), $store->refreshToken()] as $value) {
            if (! is_string($value) || trim($value) === '') {
                return false;
            }
        }

        return true;
    }

    private function authMode(): ?string
    {
        if ($this->oauthConfigured()) {
            return 'oauth_refresh_token';
        }

        if (filled(config('growth.google_search_console.access_token'))) {
            return 'static_access_token';
        }

        return null;
    }

    private function fetchOAuthAccessToken(): ?string
    {
        $store = app(GoogleSearchConsoleCredentialStore::class);

        $response = Http::asForm()
            ->timeout(15)
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'refresh_token',
                'client_id' => $store->clientId(),
                'client_secret' => $store->clientSecret(),
                'refresh_token' => $store->refreshToken(),
            ]);

        if (! $response->successful()) {
            Log::warning('GSC OAuth token exchange failed', [
                'status' => $response->status(),
                'body_preview' => mb_substr($response->body(), 0, 500),
            ]);

            return null;
        }

        $token = $response->json('access_token');

        return is_string($token) && $token !== '' ? $token : null;
    }
}
