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
        $channelWarn = (float) config('marketing.insights.channel_share_warn', 0.62);
        $engagementWarn = (float) config('marketing.insights.engagement_rate_warn_pct', 42);

        $sources = $ga4Bundle['sources'] ?? [];
        if ($sources !== []) {
            usort($sources, fn ($a, $b) => ($b['sessions'] ?? 0) <=> ($a['sessions'] ?? 0));
            $best = $sources[0];
            $insights[] = [
                'type' => 'best_source',
                'message' => __('Best traffic source (:period): :source with :sessions sessions.', [
                    'period' => trim((string) ($ga4Bundle['meta']['date_range_label'] ?? '')) !== ''
                        ? trim((string) ($ga4Bundle['meta']['date_range_label'] ?? ''))
                        : __('Rolling window'),
                    'source' => $best['source'] ?: '(direct)',
                    'sessions' => number_format((int) ($best['sessions'] ?? 0)),
                ]),
            ];
        }

        $channels = $ga4Bundle['channels'] ?? [];
        if ($channels !== []) {
            $totalCh = (int) array_sum(array_column($channels, 'sessions'));
            $topCh = $channels[0];
            if ($totalCh > 0 && (($topCh['sessions'] ?? 0) / $totalCh) >= $channelWarn) {
                $insights[] = [
                    'type' => 'channel_concentration',
                    'message' => __('Channel mix is concentrated: “:channel” drives about :pct% of sessions — diversify paid/organic tests.', [
                        'channel' => $topCh['channel'] ?? '?',
                        'pct' => number_format((($topCh['sessions'] ?? 0) / $totalCh) * 100, 0),
                    ]),
                ];
            }
        }

        $summary = $ga4Bundle['summary'] ?? [];
        $engPct = isset($summary['engagement_rate']) ? (float) $summary['engagement_rate'] : null;
        if ($engPct !== null && $engPct > 0 && $engPct < $engagementWarn) {
            $insights[] = [
                'type' => 'engagement_soft',
                'message' => __('Engagement rate is :pct% (:period) — tighten landing-page relevance and above-the-fold CTAs.', [
                    'pct' => number_format($engPct, 1),
                    'period' => trim((string) ($ga4Bundle['meta']['date_range_label'] ?? '')) !== ''
                        ? trim((string) ($ga4Bundle['meta']['date_range_label'] ?? ''))
                        : __('Rolling window'),
                ]),
            ];
        }

        $users = max(1, (int) ($summary['users'] ?? 1));
        $newUsers = (int) ($summary['new_users'] ?? 0);
        $newRatio = $newUsers / $users;
        if ($newRatio >= 0.72 && (int) ($summary['sessions'] ?? 0) > 100) {
            $insights[] = [
                'type' => 'new_user_mix',
                'message' => __('High share of new users (:pct%) — ensure remarketing tags and nurture journeys capture returning intent.', [
                    'pct' => number_format($newRatio * 100, 0),
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
    public function geminiNarrative(array $insights, ?array $ga4Bundle = null): ?string
    {
        $key = config('gemini.api_key');
        if (! is_string($key) || $key === '') {
            return null;
        }

        $lines = array_values(array_filter(array_map(fn ($i) => $i['message'] ?? '', $insights), fn ($l) => $l !== ''));

        if ($lines === [] && $ga4Bundle !== null && (int) ($ga4Bundle['summary']['sessions'] ?? 0) > 0) {
            $lines = $this->linesFromGa4Bundle($ga4Bundle);
        }

        if ($lines === []) {
            return null;
        }

        $prompt = "Summarize these marketing signals in 3 short bullet points for a digital growth platform operator in India (MarkOnMinds-style trust, clarity, no hype):\n- "
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

    /**
     * @param  array<string, mixed>  $ga4Bundle
     * @return list<string>
     */
    private function linesFromGa4Bundle(array $ga4Bundle): array
    {
        $s = $ga4Bundle['summary'] ?? [];
        $meta = $ga4Bundle['meta'] ?? [];
        $period = trim((string) ($meta['date_range_label'] ?? ''));

        $lines = [];
        $lines[] = __('GA4 (:period): :sessions sessions, :users users, :conv conversions; engagement :eng% · avg session :dur s.', [
            'period' => $period !== '' ? $period : __('selected window'),
            'sessions' => number_format((int) ($s['sessions'] ?? 0)),
            'users' => number_format((int) ($s['users'] ?? 0)),
            'conv' => number_format((int) ($s['conversions'] ?? 0)),
            'eng' => isset($s['engagement_rate']) ? number_format((float) $s['engagement_rate'], 1) : '—',
            'dur' => isset($s['avg_session_duration_sec']) ? number_format((float) $s['avg_session_duration_sec'], 1) : '—',
        ]);

        $channels = $ga4Bundle['channels'] ?? [];
        if ($channels !== []) {
            $top = $channels[0];
            $lines[] = __('Top channel (:period): :ch (:sess sessions).', [
                'period' => $period !== '' ? $period : __('window'),
                'ch' => $top['channel'] ?? '?',
                'sess' => number_format((int) ($top['sessions'] ?? 0)),
            ]);
        }

        $countries = $ga4Bundle['countries'] ?? [];
        if ($countries !== []) {
            $topC = $countries[0];
            $lines[] = __('Top country: :co (:u active users).', [
                'co' => $topC['country'] ?? '?',
                'u' => number_format((int) ($topC['users'] ?? 0)),
            ]);
        }

        return $lines;
    }

    private function geminiRequest(string $key, string $prompt): ?string
    {
        if (trim($key) === '') {
            Log::notice('Marketing Gemini narrative skipped: empty API key.');

            return null;
        }

        try {
            $res = Http::timeout(20)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $key,
                ])
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
                    [
                        'contents' => [
                            ['parts' => [['text' => $prompt]]],
                        ],
                    ]
                );

            if (! $res->successful()) {
                Log::critical('Marketing Gemini narrative HTTP failure', [
                    'status' => $res->status(),
                    'body_preview' => mb_substr($res->body(), 0, 500),
                ]);

                return null;
            }

            $text = $res->json('candidates.0.content.parts.0.text');

            return is_string($text) ? trim($text) : null;
        } catch (Throwable $e) {
            Log::critical('Marketing Gemini narrative exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
