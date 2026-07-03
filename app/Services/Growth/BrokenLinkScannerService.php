<?php

namespace App\Services\Growth;

use App\Models\Blog;
use App\Models\BrokenLinkReport;
use App\Models\Page;
use App\Models\Service;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class BrokenLinkScannerService
{
    /**
     * @return array{pages_scanned: int, links_checked: int, broken_count: int, findings: list<array<string, mixed>>}
     */
    public function scan(int $maxPages = 150): array
    {
        $urls = $this->collectCandidateUrls($maxPages);
        $findings = [];
        $checked = 0;

        foreach ($urls as $sourceUrl) {
            $html = $this->fetchBody($sourceUrl);
            if ($html === null) {
                $findings[] = [
                    'source' => $sourceUrl,
                    'target' => $sourceUrl,
                    'status' => 0,
                    'issue' => 'source_unreachable',
                ];

                continue;
            }

            foreach ($this->extractInternalLinks($html) as $target) {
                $checked++;
                $status = $this->headStatus($target);
                if ($status >= 400 || $status === 0) {
                    $findings[] = [
                        'source' => $sourceUrl,
                        'target' => $target,
                        'status' => $status,
                        'issue' => 'broken',
                    ];
                }
            }
        }

        return [
            'pages_scanned' => count($urls),
            'links_checked' => $checked,
            'broken_count' => count($findings),
            'findings' => array_slice($findings, 0, 500),
        ];
    }

    public function runAndStore(int $maxPages = 150): BrokenLinkReport
    {
        $report = BrokenLinkReport::query()->create([
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $result = $this->scan($maxPages);
            $report->update([
                'status' => 'completed',
                'pages_scanned' => $result['pages_scanned'],
                'links_checked' => $result['links_checked'],
                'broken_count' => $result['broken_count'],
                'findings' => $result['findings'],
                'completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $report->update([
                'status' => 'failed',
                'completed_at' => now(),
                'findings' => [['issue' => 'scanner_error', 'message' => $exception->getMessage()]],
            ]);
        }

        return $report->fresh() ?? $report;
    }

    /**
     * @return list<string>
     */
    private function collectCandidateUrls(int $maxPages): array
    {
        $base = rtrim((string) config('app.url'), '/');
        $urls = [$base.'/', $base.'/sitemap'];

        if (Schema::hasTable('services')) {
            Service::query()->publicListing()->orderBy('id')->limit(min(40, $maxPages))->get()
                ->each(fn (Service $service) => $urls[] = $service->publicUrl());
        }

        if (Schema::hasTable('pages')) {
            Page::query()->where('is_active', true)->orderBy('id')->limit(min(40, $maxPages))->get()
                ->each(fn (Page $page) => $urls[] = $page->publicUrl());
        }

        if (Schema::hasTable('blogs')) {
            Blog::query()->where('is_published', true)->orderByDesc('id')->limit(20)->get()
                ->each(fn (Blog $blog) => $urls[] = url('/blog/'.$blog->slug));
        }

        return array_values(array_unique(array_slice($urls, 0, $maxPages)));
    }

    /**
     * @return list<string>
     */
    private function extractInternalLinks(string $html): array
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return [];
        }

        $links = [];
        if (preg_match_all('/href=["\']([^"\']+)["\']/i', $html, $matches)) {
            foreach ($matches[1] as $href) {
                $href = trim((string) $href);
                if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                    continue;
                }

                if (str_starts_with($href, '/')) {
                    $links[] = url($href);

                    continue;
                }

                $parsedHost = parse_url($href, PHP_URL_HOST);
                if ($parsedHost === $host) {
                    $links[] = $href;
                }
            }
        }

        return array_values(array_unique($links));
    }

    private function fetchBody(string $url): ?string
    {
        try {
            $response = Http::timeout(10)->get($url);
            if (! $response->successful()) {
                return null;
            }

            return $response->body();
        } catch (Throwable) {
            return null;
        }
    }

    private function headStatus(string $url): int
    {
        try {
            $response = Http::timeout(8)->withOptions(['allow_redirects' => true])->head($url);

            return $response->status();
        } catch (Throwable) {
            return 0;
        }
    }
}
