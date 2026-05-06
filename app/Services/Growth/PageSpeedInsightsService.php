<?php

namespace App\Services\Growth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Google PageSpeed Insights API v5 — mobile performance score (0–100) for AI Pulse.
 *
 * @see https://developers.google.com/speed/docs/insights/v5/get-started
 */
class PageSpeedInsightsService
{
    /**
     * Fetch Lighthouse performance category score (0–100), or null if unavailable.
     */
    public function fetchPerformanceScore(?string $pageUrl = null): ?int
    {
        $apiKey = config('growth.pagespeed_api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            return null;
        }

        $url = $pageUrl ?? config('growth.pagespeed_target_url');
        if (! is_string($url) || trim($url) === '') {
            $url = rtrim((string) config('app.url'), '/').'/';
        }

        $cacheKey = 'growth.pagespeed.performance.'.sha1($url.'|mobile');

        return Cache::remember($cacheKey, (int) config('growth.pagespeed_cache_ttl', 21600), function () use ($apiKey, $url): ?int {
            try {
                $response = Http::timeout(90)
                    ->connectTimeout(15)
                    ->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', [
                        'url' => $url,
                        'key' => $apiKey,
                        'category' => 'performance',
                        'strategy' => 'mobile',
                    ]);

                if (! $response->successful()) {
                    Log::notice('PageSpeed Insights request failed', ['status' => $response->status(), 'url' => $url]);

                    return null;
                }

                $score = data_get($response->json(), 'lighthouseResult.categories.performance.score');
                if (! is_numeric($score)) {
                    return null;
                }

                return max(0, min(100, (int) round((float) $score * 100)));
            } catch (Throwable $e) {
                Log::notice('PageSpeed Insights exception', ['message' => $e->getMessage()]);

                return null;
            }
        });
    }

    public static function forgetScoreCache(?string $pageUrl = null): void
    {
        $url = $pageUrl ?? config('growth.pagespeed_target_url');
        if (! is_string($url) || trim($url) === '') {
            $url = rtrim((string) config('app.url'), '/').'/';
        }

        Cache::forget('growth.pagespeed.performance.'.sha1($url.'|mobile'));
    }
}
