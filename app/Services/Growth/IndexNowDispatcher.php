<?php

namespace App\Services\Growth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notifies Bing/Yandex IndexNow when public URLs change (no paid APIs).
 */
final class IndexNowDispatcher
{
    /**
     * @param  list<string>  $absoluteUrls
     */
    public function notify(array $absoluteUrls): void
    {
        if (! config('growth.indexnow.enabled', true)) {
            return;
        }

        $key = app(SearchEngineDiscoveryService::class)->resolveIndexNowKey();
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        if ($key === '' || ! is_string($host) || $host === '') {
            return;
        }

        $urls = array_values(array_unique(array_filter(
            $absoluteUrls,
            static fn (mixed $url): bool => is_string($url) && $url !== '' && filter_var($url, FILTER_VALIDATE_URL)
        )));

        if ($urls === []) {
            return;
        }

        $endpoint = (string) config('growth.indexnow.endpoint', 'https://api.indexnow.org/indexnow');
        $payload = [
            'host' => $host,
            'key' => $key,
            'keyLocation' => url('/'.ltrim((string) config('growth.indexnow.key_path', $key.'.txt'), '/')),
            'urlList' => array_slice($urls, 0, 10000),
        ];

        try {
            Http::timeout(8)->post($endpoint, $payload);
        } catch (Throwable $exception) {
            Log::warning('indexnow.notify_failed', [
                'message' => $exception->getMessage(),
                'count' => count($urls),
            ]);
        }
    }

    public function notifyPath(string $path): void
    {
        $normalized = '/'.ltrim($path, '/');
        $this->notify([url($normalized)]);
    }
}
