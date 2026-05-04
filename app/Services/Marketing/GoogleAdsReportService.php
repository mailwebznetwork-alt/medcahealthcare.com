<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleAdsReportService
{
    /**
     * Partial API: returns live structure when Google Ads API env is complete, else a safe placeholder.
     *
     * @return array{campaigns: list<array{name: string, clicks: int, cost: float, conversions: float}>, note: string|null, configured: bool}
     */
    public function fetchSummary(): array
    {
        $token = config('marketing.google_ads_developer_token');
        $customerId = config('marketing.google_ads_customer_id');

        if (! is_string($token) || $token === '' || ! is_string($customerId) || $customerId === '') {
            return [
                'campaigns' => [],
                'note' => __('Connect Google Ads API credentials (developer token, OAuth refresh token, customer ID) in .env for live campaign metrics. Event-level conversions remain visible in GA4.'),
                'configured' => false,
            ];
        }

        return Cache::remember('marketing.google_ads.summary', 3600, function (): array {
            try {
                // REST placeholder: wire googleads/google-ads-php when you need full parity.
                // Keeps the Marketing module lightweight while exposing the integration seam.
                Log::info('Google Ads API summary skipped — use GA4 for conversion attribution until REST client is wired.');

                return [
                    'campaigns' => [],
                    'note' => __('Google Ads API credentials detected; aggregate REST queries can be enabled here. Until then, use GA4 for clicks and conversion events tied to Ads.'),
                    'configured' => true,
                ];
            } catch (Throwable $e) {
                Log::warning('Google Ads summary failed', ['message' => $e->getMessage()]);

                return [
                    'campaigns' => [],
                    'note' => $e->getMessage(),
                    'configured' => true,
                ];
            }
        });
    }
}
