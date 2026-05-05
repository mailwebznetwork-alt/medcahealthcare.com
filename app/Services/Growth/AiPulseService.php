<?php

namespace App\Services\Growth;

use App\Jobs\RefreshAiPulseSnapshotJob;
use App\Models\Block;
use App\Models\Blog;
use App\Models\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        RefreshAiPulseSnapshotJob::dispatch($force);

        return $this->placeholderSnapshot();
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
     * Rebuild cache synchronously and return the new snapshot (e.g. after Fix with AI).
     *
     * @return array<string, mixed>
     */
    public function rebuildAndRead(): array
    {
        $this->rebuildSnapshotCache(false);
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

        $speedMean = (int) config('growth.ai_pulse_speed_baseline', 72);
        $brokenCount = count($broken);
        $brandAuthority = $this->brandAuthority($rankMath, $aioMean, $speedMean, $brokenCount);
        $recommendations = $this->recommendations($broken, $rankMath, $speedMean, $brandAuthority);

        return [
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
                'brand_authority' => $brandAuthority,
            ],
            'free_tier_sources' => [
                'gemini' => [
                    'model' => 'gemini-2.0-flash',
                    'source' => config('gemini.api_key') ? 'configured' : 'not_configured',
                ],
            ],
            'broken_links' => array_values($broken),
            'recommendations' => $recommendations,
        ];
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
            '/blog' => true,
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
                $out[] = ['scope' => $scope, 'id' => $id, 'title' => $title, 'url' => $href, 'reason' => 'empty_or_damaged'];

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
    private function recommendations(array $broken, int $rankMath, int $speed, int $authority): array
    {
        $out = [];
        if ($broken !== []) {
            $out[] = __('Resolve broken or suspicious internal links — use Fix with AI or edit the source.');
        }
        if ($speed < 70) {
            $out[] = __('Review Core Web Vitals and heavy assets; baseline speed score is heuristic until PageSpeed is wired.');
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
     * @return array<string, mixed>
     */
    private function placeholderSnapshot(): array
    {
        return [
            'scanned_at' => __('Scan in progress'),
            'scan_in_progress' => true,
            'totals' => ['pages' => 0, 'blogs' => 0, 'blocks' => 0],
            'scores' => ['speed' => 0, 'rankmath' => 0, 'brand_authority' => 0],
            'free_tier_sources' => [],
            'broken_links' => [],
            'recommendations' => [__('AI Pulse scan is running in the background. Refresh shortly.')],
        ];
    }
}
