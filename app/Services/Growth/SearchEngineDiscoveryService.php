<?php

namespace App\Services\Growth;

use App\Support\SeoTechnicalResolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notifies search engines after sitemap updates (GSC API + IndexNow).
 */
final class SearchEngineDiscoveryService
{
    public function __construct(
        private readonly GoogleSearchConsoleService $gsc,
        private readonly IndexNowDispatcher $indexNow,
    ) {}

    /**
     * @return array{gsc_submitted: bool, indexnow_notified: bool, messages: list<string>}
     */
    public function pingAfterSitemapUpdate(): array
    {
        $messages = [];
        $sitemapUrl = url('/sitemap.xml');
        $gscSubmitted = false;
        $indexNowNotified = false;

        $siteUrl = (string) (config('growth.google_search_console.site_url') ?: config('app.url'));
        $gscResult = $this->gsc->submitSitemap($siteUrl, $sitemapUrl);
        if ($gscResult['submitted'] ?? false) {
            $gscSubmitted = true;
            $messages[] = __('Sitemap submitted to Google Search Console.');
        } elseif (filled($gscResult['error'] ?? null)) {
            $messages[] = __('GSC sitemap: ').$gscResult['error'];
        }

        try {
            $this->indexNow->notify([$sitemapUrl]);
            $indexNowNotified = true;
            $messages[] = __('IndexNow notified with sitemap URL.');
        } catch (Throwable $exception) {
            Log::warning('search_engine_discovery.indexnow_failed', ['message' => $exception->getMessage()]);
            $messages[] = __('IndexNow notification skipped or failed.');
        }

        $this->pingBingSitemap($sitemapUrl);

        return [
            'gsc_submitted' => $gscSubmitted,
            'indexnow_notified' => $indexNowNotified,
            'messages' => $messages,
        ];
    }

    public function resolveIndexNowKey(): string
    {
        $technical = SeoTechnicalResolver::forCurrentSite(repairOrphan: false);
        $fromDb = trim((string) ($technical?->indexnow_key ?? ''));

        if ($fromDb !== '') {
            return $fromDb;
        }

        return trim((string) config('growth.indexnow.key', ''));
    }

    private function pingBingSitemap(string $sitemapUrl): void
    {
        try {
            Http::timeout(6)->get('https://www.bing.com/ping', ['sitemap' => $sitemapUrl]);
        } catch (Throwable $exception) {
            Log::debug('bing.sitemap_ping_skipped', ['message' => $exception->getMessage()]);
        }
    }
}
