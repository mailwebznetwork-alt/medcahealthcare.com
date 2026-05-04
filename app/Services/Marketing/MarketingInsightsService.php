<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MarketingInsightsService
{
    /**
     * @param  array<string, mixed>  $ga4Bundle
     * @param  array<string, mixed>  $googleAds
     * @param  array<string, mixed>  $metaAds
     * @param  list<array<string, mixed>>  $snapshots
     * @return list<array{type: string, message: string}>
     */
    public function ruleBasedInsights(
        array $ga4Bundle,
        array $googleAds,
        array $metaAds,
        array $snapshots
    ): array {
        $insights = [];
        $warnCost = (float) config('marketing.insights.cost_per_lead_warn', 500);
        $waRatio = (float) config('marketing.insights.whatsapp_click_ratio_high', 0.15);

        $sources = $ga4Bundle['sources'] ?? [];
        if ($sources !== []) {
            usort($sources, fn ($a, $b) => ($b['sessions'] ?? 0) <=> ($a['sessions'] ?? 0));
            $best = $sources[0];
            $insights[] = [
                'type' => 'best_source',
                'message' => __('Best traffic source (28d): :source with :sessions sessions.', [
                    'source' => $best['source'] ?: '(direct)',
                    'sessions' => number_format((int) ($best['sessions'] ?? 0)),
                ]),
            ];
        }

        $events = $ga4Bundle['events'] ?? [];
        $wa = 0;
        $sessions = max(1, (int) ($ga4Bundle['summary']['sessions'] ?? 1));
        foreach ($events as $ev) {
            if (($ev['name'] ?? '') === 'whatsapp_click') {
                $wa += (int) ($ev['count'] ?? 0);
            }
        }
        if ($wa > 0 && ($wa / $sessions) >= $waRatio) {
            $insights[] = [
                'type' => 'whatsapp_velocity',
                'message' => __('WhatsApp engagement is elevated (:count clicks vs :sessions sessions — review staffing on WhatsApp).', [
                    'count' => number_format($wa),
                    'sessions' => number_format($sessions),
                ]),
            ];
        }

        foreach ($googleAds['campaigns'] ?? [] as $c) {
            $cost = (float) ($c['cost'] ?? 0);
            $conv = (float) ($c['conversions'] ?? 0);
            if ($cost >= $warnCost && $conv < 1) {
                $insights[] = [
                    'type' => 'ads_efficiency',
                    'message' => __('Campaign “:name” shows spend without conversions — validate audiences and GA4-linked goals.', [
                        'name' => $c['name'] ?? '?',
                    ]),
                ];
                break;
            }
        }

        $sms = collect($snapshots)->firstWhere('channel', 'sms');
        if ($sms && (($sms['metrics']['delivered'] ?? 0) > 0 && ($sms['metrics']['sent'] ?? 0) > 0)) {
            $ratio = (float) $sms['metrics']['delivered'] / (float) $sms['metrics']['sent'];
            if ($ratio < 0.85) {
                $insights[] = [
                    'type' => 'sms_delivery',
                    'message' => __('SMS delivery rate is below 85% for the last manual snapshot — review numbers and template quality.'),
                ];
            }
        }

        return $insights;
    }

    /**
     * Optional Gemini narrative (uses config('gemini.api_key')).
     *
     * @param  list<array{type: string, message: string}>  $insights
     */
    public function geminiNarrative(array $insights): ?string
    {
        $key = config('gemini.api_key');
        if (! is_string($key) || $key === '' || $insights === []) {
            return null;
        }

        $lines = array_map(fn ($i) => $i['message'] ?? '', $insights);
        $prompt = "Summarize these marketing signals in 2 short bullet points for a healthcare operator in India:\n- "
            .implode("\n- ", $lines);

        $cacheKey = 'marketing.gemini.'.sha1($prompt);

        return Cache::remember($cacheKey, 3600, function () use ($key, $prompt): ?string {
            try {
                return $this->geminiRequest($key, $prompt);
            } catch (Throwable $e) {
                Log::notice('Gemini marketing narrative skipped', ['message' => $e->getMessage()]);

                return null;
            }
        });
    }

    private function geminiRequest(string $key, string $prompt): ?string
    {
        try {
            $res = Http::timeout(20)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key='.$key,
                    [
                        'contents' => [
                            ['parts' => [['text' => $prompt]]],
                        ],
                    ]
                );

            if (! $res->successful()) {
                return null;
            }

            $text = $res->json('candidates.0.content.parts.0.text');

            return is_string($text) ? trim($text) : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}
