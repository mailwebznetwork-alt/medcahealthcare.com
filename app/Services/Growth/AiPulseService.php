<?php

namespace App\Services\Growth;

use App\Jobs\RefreshAiPulseSnapshotJob;
use App\Models\Block;
use App\Models\Blog;
use App\Models\Lead;
use App\Models\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AiPulseService
{
    private const string CACHE_KEY = 'markonminds:ai_pulse:snapshot:v1';

    private const int CACHE_HOURS = 24;

    /**
     * @return array<string, mixed>
     */
    public function snapshot(bool $force = false): array
    {
        return $this->cachedSnapshotOrDispatch($force);
    }

    public function triggerAuditAfterPublish(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable) {
        }
        RefreshAiPulseSnapshotJob::dispatch(true);
    }

    /**
     * Cached snapshot, or a synchronous rebuild when missing so AI Pulse works without a queue worker.
     *
     * @return array<string, mixed>
     */
    public function cachedSnapshotOrDispatch(bool $force = false): array
    {
        if ($force) {
            try {
                Cache::forget(self::CACHE_KEY);
            } catch (Throwable) {
            }
        }
        try {
            $cached = Cache::get(self::CACHE_KEY);
            if (is_array($cached) && $cached !== []) {
                return $cached;
            }
        } catch (Throwable) {
        }

        return $this->rebuildAndRead($force);
    }

    public function rebuildSnapshotCache(bool $force = false): void
    {
        if ($force) {
            try {
                Cache::forget(self::CACHE_KEY);
            } catch (Throwable) {
            }
        }
        try {
            Cache::put(self::CACHE_KEY, $this->buildSnapshot(), now()->addHours(self::CACHE_HOURS));
        } catch (Throwable) {
        }
    }

    /**
     * Rebuild cache synchronously and return the new snapshot (e.g. after Fix with AI, Refresh snapshot, or cold cache).
     *
     * @return array<string, mixed>
     */
    public function rebuildAndRead(bool $force = false): array
    {
        if ($force) {
            PageSpeedInsightsService::forgetScoreCache();
        }
        $this->rebuildSnapshotCache($force);
        try {
            $cached = Cache::get(self::CACHE_KEY);
            if (is_array($cached) && $cached !== []) {
                return $cached;
            }
        } catch (Throwable) {
        }

        return $this->placeholderSnapshot();
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function fixWithAi(string $scope, int $id, string $brokenUrl): array
    {
        $brokenUrl = trim($brokenUrl);
        if ($brokenUrl === '') {
            return ['success' => false, 'message' => __('Broken URL missing.')];
        }
        $replacement = $this->suggestReplacement($brokenUrl);
        if ($replacement === '') {
            $replacement = '/';
        }

        if ($scope === 'page') {
            $model = Page::query()->find($id);
            if (! $model instanceof Page) {
                return ['success' => false, 'message' => __('Page not found.')];
            }
            $model->update(['content' => str_replace($brokenUrl, $replacement, (string) $model->content)]);
        } elseif ($scope === 'blog') {
            $model = Blog::query()->find($id);
            if (! $model instanceof Blog) {
                return ['success' => false, 'message' => __('Blog not found.')];
            }
            $model->update(['content' => str_replace($brokenUrl, $replacement, (string) $model->content)]);
        } elseif ($scope === 'block') {
            $model = Block::query()->find($id);
            if (! $model instanceof Block) {
                return ['success' => false, 'message' => __('Block not found.')];
            }
            $model->update(['code' => str_replace($brokenUrl, $replacement, (string) $model->code)]);
        } else {
            return ['success' => false, 'message' => __('Unsupported scope.')];
        }

        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable) {
        }

        return ['success' => true, 'message' => __('Link updated to :path.', ['path' => $replacement])];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSnapshot(): array
    {
        $pages = Page::query()->where('is_active', true)->orderBy('id')->get();
        $blogs = Blog::query()
            ->where('is_published', true)
            ->where(function ($q): void {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderBy('id')
            ->get();
        $blocks = Block::query()->where('is_active', true)->orderBy('id')->get();

        $knownInternal = $this->knownPaths($pages, $blogs);

        $broken = [];
        foreach ($pages as $page) {
            $html = (string) $page->content;
            $broken = array_merge($broken, $this->scanLinks($html, 'page', (int) $page->id, (string) $page->title, $knownInternal));
        }
        foreach ($blogs as $blog) {
            $html = (string) $blog->content;
            $broken = array_merge($broken, $this->scanLinks($html, 'blog', (int) $blog->id, (string) $blog->title, $knownInternal));
        }
        foreach ($blocks as $block) {
            $html = (string) $block->code;
            $broken = array_merge($broken, $this->scanLinks($html, 'block', (int) $block->id, (string) $block->block_name, $knownInternal));
        }

        $seoScores = [];
        $aioScores = [];
        foreach ($pages as $page) {
            $seoScores[] = $this->seoScoreForPageOrBlog($page->meta_title, $page->meta_description, $page->h1, $page->title);
            $aioScores[] = $this->aioScore($page->aeo_question, $page->aeo_answer, $page->schema_json);
        }
        foreach ($blogs as $blog) {
            $seoScores[] = $this->seoScoreForPageOrBlog($blog->meta_title, $blog->meta_description, $blog->h1, $blog->title);
            $aioScores[] = $this->aioScore($blog->aeo_question, $blog->aeo_answer, $blog->schema_json);
        }
        $rankMath = $seoScores === [] ? 0 : (int) round(array_sum($seoScores) / count($seoScores));
        $aioMean = $aioScores === [] ? 0 : (int) round(array_sum($aioScores) / count($aioScores));

        $pageSpeedScore = app(PageSpeedInsightsService::class)->fetchPerformanceScore();
        $speedMean = $pageSpeedScore ?? (int) config('growth.ai_pulse_speed_baseline', 72);
        $speedSource = $pageSpeedScore !== null ? 'pagespeed_insights' : 'baseline';

        $brokenCount = count($broken);
        $brandAuthority = $this->brandAuthority($rankMath, $aioMean, $speedMean, $brokenCount);
        $recommendations = $this->recommendations($broken, $rankMath, $speedMean, $brandAuthority, $speedSource);

        $payload = [
            'scanned_at' => now()->toDateTimeString(),
            'scan_in_progress' => false,
            'totals' => [
                'pages' => $pages->count(),
                'blogs' => $blogs->count(),
                'blocks' => $blocks->count(),
            ],
            'scores' => [
                'speed' => $speedMean,
                'rankmath' => $rankMath,
                'aio' => $aioMean,
                'brand_authority' => $brandAuthority,
            ],
            'speed_detail' => [
                'source' => $speedSource,
                'score_0_100' => $speedMean,
            ],
            'free_tier_sources' => [
                'gemini' => [
                    'model' => 'gemini-2.0-flash',
                    'source' => config('gemini.api_key') ? 'configured' : 'not_configured',
                ],
                'pagespeed' => [
                    'source' => $speedSource === 'pagespeed_insights' ? 'live' : 'not_configured',
                ],
            ],
            'broken_links' => array_values($broken),
            'recommendations' => $recommendations,
        ];

        $payload['pulse_narrative'] = $this->buildPulseNarrativeInsights($payload);

        return $payload;
    }

    /**
     * @param  Collection<int, Page>  $pages
     * @param  Collection<int, Blog>  $blogs
     * @return array<string, bool>
     */
    private function knownPaths(Collection $pages, Collection $blogs): array
    {
        $paths = [
            '/' => true,
            '/about' => true,
            '/contact' => true,
            '/blog' => true,
            '/careers' => true,
        ];
        foreach ($pages as $page) {
            $paths['/p/'.ltrim((string) $page->slug, '/')] = true;
        }
        foreach ($blogs as $blog) {
            $paths['/blog/'.ltrim((string) $blog->slug, '/')] = true;
        }

        return $paths;
    }

    /**
     * @param  array<string, bool>  $knownInternal
     * @return list<array{scope: string, id: int, title: string, url: string, reason: string}>
     */
    private function scanLinks(string $html, string $scope, int $id, string $title, array $knownInternal): array
    {
        $out = [];
        if ($html === '') {
            return $out;
        }
        preg_match_all('/href\s*=\s*["\']([^"\']*)["\']/i', $html, $m);
        foreach (array_values(array_unique($m[1] ?? [])) as $href) {
            $href = trim((string) $href);
            if ($href === '' || $href === '#' || str_starts_with(strtolower($href), 'javascript:')) {
                continue;
            }
            if (str_starts_with($href, '/')) {
                $normalized = $href;
                if (! isset($knownInternal[$normalized])) {
                    $out[] = ['scope' => $scope, 'id' => $id, 'title' => $title, 'url' => $href, 'reason' => 'missing_internal_target'];
                }
            }
        }

        return $out;
    }

    private function seoScoreForPageOrBlog(?string $metaTitle, ?string $metaDescription, ?string $h1, ?string $title): int
    {
        $score = 0;
        $mt = trim((string) $metaTitle);
        if ($mt !== '' && strlen($mt) >= 10 && strlen($mt) <= 70) {
            $score += 35;
        } elseif ($mt !== '') {
            $score += 15;
        }
        $md = trim((string) $metaDescription);
        if ($md !== '' && strlen($md) >= 50 && strlen($md) <= 200) {
            $score += 35;
        } elseif ($md !== '') {
            $score += 15;
        }
        $h = trim((string) $h1);
        if ($h !== '' || trim((string) $title) !== '') {
            $score += 30;
        }

        return min(100, $score);
    }

    /**
     * @param  array<string, mixed>|null  $schema
     */
    private function aioScore(?string $q, ?string $a, ?array $schema): int
    {
        $score = 0;
        if (filled(trim((string) $q))) {
            $score += 25;
        }
        if (filled(trim((string) $a))) {
            $score += 25;
        }
        if (is_array($schema) && $schema !== []) {
            $score += 50;
        }

        return min(100, $score);
    }

    private function brandAuthority(int $seo, int $aio, int $speed, int $brokenCount): int
    {
        $fallback = max(0, min(100, (int) round((0.35 * $seo) + (0.45 * $aio) + (0.2 * $speed) - min(20, $brokenCount * 2))));
        $key = config('gemini.api_key');
        if (! is_string($key) || $key === '') {
            return $fallback;
        }
        $prompt = "Given SEO {$seo}, AIO {$aio}, SPEED {$speed}, broken links {$brokenCount}, return ONLY one integer 0-100 Brand Authority Score for AI readability.";
        try {
            $resp = trim((string) $this->geminiGenerateText($key, $prompt));
            if (preg_match('/\b([0-9]{1,3})\b/', $resp, $m) === 1) {
                return max(0, min(100, (int) $m[1]));
            }
        } catch (Throwable) {
        }

        return $fallback;
    }

    /**
     * @param  list<array{scope: string, id: int, title: string, url: string, reason: string}>  $broken
     * @return list<string>
     */
    private function recommendations(array $broken, int $rankMath, int $speed, int $authority, string $speedSource = 'baseline'): array
    {
        $out = [];
        if ($broken !== []) {
            $out[] = __('Resolve broken or suspicious internal links — use Fix with AI or edit the source.');
        }
        if ($speed < 70) {
            if ($speedSource === 'pagespeed_insights') {
                $out[] = __('Mobile PageSpeed score is :n — prioritize LCP element, cut render-blocking JS, and compress large hero images.', ['n' => (string) $speed]);
            } else {
                $out[] = __('Review Core Web Vitals and heavy assets; set GOOGLE_PAGESPEED_API_KEY for live Lighthouse scores or adjust AI_PULSE_SPEED_BASELINE.');
            }
        }
        if ($rankMath < 70) {
            $out[] = __('Improve meta titles, descriptions, and H1 coverage across pages and blogs.');
        }
        if ($authority < 75) {
            $out[] = __('Add AEO Q&A and structured data; align NAP in Growth Center SEO.');
        }

        return $out !== [] ? $out : [__('AI Pulse: key on-page signals look healthy.')];
    }

    private function suggestReplacement(string $brokenUrl): string
    {
        $pages = Page::query()->where('is_active', true)->get();
        $blogs = Blog::query()->where('is_published', true)->get();
        $paths = array_keys($this->knownPaths($pages, $blogs));
        $fallback = $paths[0] ?? '/';
        $key = config('gemini.api_key');
        if (! is_string($key) || $key === '') {
            return $fallback;
        }
        $prompt = "Broken internal link: {$brokenUrl}\nChoose the best replacement from this list only:\n".implode("\n", $paths)."\nReturn only one path.";
        try {
            $txt = trim((string) $this->geminiGenerateText($key, $prompt));
            foreach ($paths as $p) {
                if (str_contains($txt, $p)) {
                    return $p;
                }
            }
        } catch (Throwable) {
        }

        return $fallback;
    }

    private function geminiGenerateText(string $apiKey, string $prompt): string
    {
        $res = Http::timeout(25)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key='.$apiKey,
                [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                ]
            );
        if (! $res->successful()) {
            Log::notice('AI Pulse Gemini request failed', ['status' => $res->status()]);

            return '';
        }
        $data = $res->json();
        $text = data_get($data, 'candidates.0.content.parts.0.text');

        return is_string($text) ? $text : '';
    }

    /**
     * Narrative pillars: business health, predictive, conversion, visibility (GEO/AEO).
     *
     * @param  array<string, mixed>  $snapshot
     * @return array{
     *     business_health: string,
     *     predictive_insights: string,
     *     conversion_insights: string,
     *     visibility_geo_aeo: string,
     *     source: string,
     *     lead_counts_30d: array<string, int>
     * }
     */
    private function buildPulseNarrativeInsights(array $snapshot): array
    {
        $leadCounts = $this->leadSourceCountsLastDays(30);
        $apiKey = config('gemini.api_key');

        $base = [
            'business_health' => '',
            'predictive_insights' => '',
            'conversion_insights' => '',
            'visibility_geo_aeo' => '',
            'source' => 'heuristic',
            'lead_counts_30d' => $leadCounts,
        ];

        if (is_string($apiKey) && $apiKey !== '') {
            $parsed = $this->geminiPulseNarrativeJson($apiKey, $snapshot, $leadCounts);
            if ($parsed !== null) {
                return array_merge($base, $parsed, ['source' => 'gemini', 'lead_counts_30d' => $leadCounts]);
            }
        }

        return array_merge($base, $this->heuristicPulseNarrative($snapshot, $leadCounts));
    }

    /**
     * @return array<string, int>
     */
    private function leadSourceCountsLastDays(int $days): array
    {
        if (! Schema::hasTable('leads')) {
            return [];
        }

        $rows = Lead::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('source, COUNT(*) as c')
            ->groupBy('source')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $k = (string) ($row->source ?? '');
            if ($k !== '') {
                $out[$k] = (int) $row->c;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, int>  $leadCounts
     * @return array{business_health: string, predictive_insights: string, conversion_insights: string, visibility_geo_aeo: string}|null
     */
    private function geminiPulseNarrativeJson(string $apiKey, array $snapshot, array $leadCounts): ?array
    {
        $seoMean = (int) data_get($snapshot, 'scores.rankmath', 0);
        $authority = (int) data_get($snapshot, 'scores.brand_authority', 0);
        $brokenN = count(data_get($snapshot, 'broken_links', []));

        $ctx = json_encode([
            'lead_counts_by_source_last_30d' => $leadCounts,
            'on_page_seo_mean' => $seoMean,
            'aeo_structured_data_mean' => (int) data_get($snapshot, 'scores.aio', 0),
            'brand_authority' => $authority,
            'broken_internal_links' => $brokenN,
            'active_pages' => data_get($snapshot, 'totals.pages'),
            'published_blogs' => data_get($snapshot, 'totals.blogs'),
        ], JSON_UNESCAPED_UNICODE) ?: '{}';

        $prompt = <<<TXT
You are MarkOnMinds / Medca Growth OS — AI Pulse narrative brief.

Return ONLY valid JSON with keys:
"business_health","predictive_insights","conversion_insights","visibility_geo_aeo"

Rules:
- Each value is ONE string of 2 to 4 sentences.
- Audience: healthcare leadership in Bengaluru; practical, no hype.
- Ground insights in this context JSON when possible:
{$ctx}

Do not use markdown code fences. JSON only.
TXT;

        $raw = trim($this->geminiGenerateText($apiKey, $prompt));
        if ($raw === '') {
            return null;
        }

        $decoded = $this->decodeJsonObjectFromGemini($raw);
        if ($decoded === null) {
            return null;
        }

        $keys = ['business_health', 'predictive_insights', 'conversion_insights', 'visibility_geo_aeo'];
        $out = [];
        foreach ($keys as $key) {
            $v = $decoded[$key] ?? '';
            $out[$key] = is_string($v) ? trim($v) : '';
        }

        if ($out['business_health'] === '' && $out['predictive_insights'] === '') {
            return null;
        }

        return $out;
    }

    private function decodeJsonObjectFromGemini(string $raw): ?array
    {
        $trim = trim($raw);
        if ($trim === '') {
            return null;
        }
        if (str_starts_with($trim, '```')) {
            $trim = preg_replace('/^```(?:json)?\s*/i', '', $trim) ?? $trim;
            $trim = preg_replace('/\s*```\s*$/', '', $trim) ?? $trim;
        }
        try {
            $data = json_decode($trim, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        return is_array($data) ? $data : null;
    }

    /**
     * @param  array<string, int>  $leadCounts
     * @return array{business_health: string, predictive_insights: string, conversion_insights: string, visibility_geo_aeo: string}
     */
    private function heuristicPulseNarrative(array $snapshot, array $leadCounts): array
    {
        $totalLeads = array_sum($leadCounts);
        $seoMean = (int) data_get($snapshot, 'scores.rankmath', 0);
        $authority = (int) data_get($snapshot, 'scores.brand_authority', 0);
        $brokenN = count(data_get($snapshot, 'broken_links', []));

        $business = $totalLeads === 0
            ? __('No inquiries recorded in the last 30 days — tighten acquisition or verify lead capture endpoints.')
            : __('Last 30 days: :count inquiries by channel — route follow-up in Operations → Bookings.', ['count' => $totalLeads]);

        $predictive = __('If recent inquiry velocity stays flat, expect slower pipeline growth next month unless traffic or conversion experiments change.')

            .' '
            .__('Watch Organic vs paid sources in Integrations-backed analytics.');

        $conversion = $totalLeads > 0
            ? __('Compare channel counts against GA4 sessions and events in Growth Center → GA4 to spot gaps between visits and inquiries.')
            : __('Once inquiries appear, compare source mix with GA4 acquisition to prioritize budget.');

        $visibility = __('On-page SEO mean is :seo and AI readiness score is :auth.', ['seo' => $seoMean, 'auth' => $authority])
            .' '
            .($brokenN > 0
                ? __('Fix :n broken internal links to protect crawl paths and AEO clarity.', ['n' => $brokenN])
                : __('Internal links look consistent — extend GEO entity copy in Growth Center SEO tabs.'));

        return [
            'business_health' => $business,
            'predictive_insights' => $predictive,
            'conversion_insights' => $conversion,
            'visibility_geo_aeo' => $visibility,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function placeholderSnapshot(): array
    {
        return [
            'scanned_at' => __('Scan in progress'),
            'scan_in_progress' => true,
            'totals' => ['pages' => 0, 'blogs' => 0, 'blocks' => 0],
            'scores' => ['speed' => 0, 'rankmath' => 0, 'aio' => 0, 'brand_authority' => 0],
            'speed_detail' => ['source' => 'placeholder', 'score_0_100' => 0],
            'free_tier_sources' => [],
            'broken_links' => [],
            'recommendations' => [__('AI Pulse scan is running in the background. Refresh shortly.')],
            'pulse_narrative' => [
                'business_health' => '',
                'predictive_insights' => '',
                'conversion_insights' => '',
                'visibility_geo_aeo' => '',
                'source' => 'placeholder',
                'lead_counts_30d' => [],
            ],
        ];
    }
}
