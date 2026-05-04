<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MetaAdsReportService
{
    /**
     * Partial API: Insights edge when token + ad account are present.
     *
     * @return array{campaigns: list<array{name: string, reach: int, clicks: int, leads: int}>, note: string|null, configured: bool}
     */
    public function fetchSummary(): array
    {
        $token = config('marketing.meta_access_token');
        $accountId = config('marketing.meta_ad_account_id');

        if (! is_string($token) || $token === '' || ! is_string($accountId) || $accountId === '') {
            return [
                'campaigns' => [],
                'note' => __('Set META_MARKETING_ACCESS_TOKEN and META_AD_ACCOUNT_ID to pull reach, clicks, and lead metrics from the Marketing API.'),
                'configured' => false,
            ];
        }

        return Cache::remember('marketing.meta.ads.summary', 3600, function () use ($token, $accountId): array {
            try {
                $adAct = preg_replace('/^act_/i', '', (string) $accountId);
                $adAct = preg_replace('/\D/', '', $adAct);
                $url = 'https://graph.facebook.com/v21.0/act_'.$adAct.'/insights';

                $response = Http::timeout(15)->withToken($token)->get($url, [
                    'fields' => 'campaign_name,reach,inline_link_clicks,actions',
                    'level' => 'campaign',
                    'date_preset' => 'last_28d',
                    'limit' => 25,
                ]);

                if (! $response->successful()) {
                    return [
                        'campaigns' => [],
                        'note' => $response->json('error.message') ?? $response->body(),
                        'configured' => true,
                    ];
                }

                $data = $response->json('data') ?? [];
                $campaigns = [];
                foreach ($data as $row) {
                    $leads = 0;
                    foreach ($row['actions'] ?? [] as $action) {
                        if (($action['action_type'] ?? '') === 'lead') {
                            $leads += (int) ($action['value'] ?? 0);
                        }
                    }
                    $campaigns[] = [
                        'name' => (string) ($row['campaign_name'] ?? __('Campaign')),
                        'reach' => (int) ($row['reach'] ?? 0),
                        'clicks' => (int) ($row['inline_link_clicks'] ?? 0),
                        'leads' => $leads,
                    ];
                }

                return [
                    'campaigns' => $campaigns,
                    'note' => null,
                    'configured' => true,
                ];
            } catch (Throwable $e) {
                Log::warning('Meta Ads summary failed', ['message' => $e->getMessage()]);

                return [
                    'campaigns' => [],
                    'note' => $e->getMessage(),
                    'configured' => true,
                ];
            }
        });
    }
}
