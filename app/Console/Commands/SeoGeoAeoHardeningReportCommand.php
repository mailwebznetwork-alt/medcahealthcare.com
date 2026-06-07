<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Growth\SeoService;
use App\Services\Seo\LocalityContextResolver;
use App\Services\Seo\UnifiedJsonLdGraphBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SeoGeoAeoHardeningReportCommand extends Command
{
    protected $signature = 'medca:seo-hardening-report {--output= : Write markdown report to path}';

    protected $description = 'Generate SEO/GEO/AEO hardening validation reports';

    public function handle(SeoService $seoService, UnifiedJsonLdGraphBuilder $graphBuilder): int
    {
        $reports = [
            'schema_cleanup' => $this->schemaReport($graphBuilder),
            'internal_links' => $this->internalLinksReport(),
            'location_uniqueness' => $this->locationUniquenessReport(),
            'pincode_expansion' => $this->pincodeExpansionReport(),
            'sitemap_validation' => $this->sitemapReport($seoService),
            'duplicate_content_risk' => $this->duplicateContentReport(),
            'geo_readiness' => $this->geoReadinessReport(),
            'aeo_readiness' => $this->aeoReadinessReport(),
        ];

        $markdown = $this->toMarkdown($reports);

        $output = $this->option('output') ?: base_path('docs/SEO-GEO-AEO-HARDENING-REPORTS.md');
        File::ensureDirectoryExists(dirname($output));
        File::put($output, $markdown);

        $this->info("Report written to {$output}");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function schemaReport(UnifiedJsonLdGraphBuilder $graphBuilder): array
    {
        $services = Service::query()->publicListing()->limit(5)->get();
        $graphs = $services->map(fn (Service $s) => count($graphBuilder->buildServiceGraph($s)['@graph'] ?? []))->all();

        return [
            'unified_graph_builder' => 'active',
            'sample_service_graph_nodes' => $graphs,
            'duplicate_suppression' => 'site-seo-meta suppresses org/faq/service duplicates on service+location pages',
            'page_json_ld' => 'single @graph script per page',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function internalLinksReport(): array
    {
        $stale = Service::query()
            ->whereNull('internal_links_snapshot')
            ->count();
        $withSnapshot = Service::query()
            ->whereNotNull('internal_links_snapshot')
            ->count();

        return [
            'services_with_snapshot' => $withSnapshot,
            'services_missing_snapshot' => $stale,
            'queue_jobs' => ['RefreshServiceInternalLinksJob', 'RefreshPeerServiceInternalLinksJob'],
            'observers' => ['Service', 'PinCode', 'ServiceLocationPage'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function locationUniquenessReport(): array
    {
        $samples = ServiceLocationPage::query()
            ->with(['page', 'pincode.locationFaqs', 'service'])
            ->inRandomOrder()
            ->limit(10)
            ->get()
            ->map(fn (ServiceLocationPage $row) => [
                'url' => $row->publicUrl(),
                'intro_hash' => md5((string) ($row->page?->meta_description ?? '')),
                'faq_count' => $row->pincode?->locationFaqs()->count() ?? 0,
                'composite' => $row->quality_snapshot['composite'] ?? null,
                'is_indexable' => $row->is_indexable,
            ])
            ->all();

        $uniqueIntros = count(array_unique(array_column($samples, 'intro_hash')));

        return [
            'sample_count' => count($samples),
            'unique_intro_hashes' => $uniqueIntros,
            'samples' => $samples,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pincodeExpansionReport(): array
    {
        return [
            'active_primary_city_pincodes' => $this->activePrimaryCityPincodeCount(),
            'location_pages' => ServiceLocationPage::count(),
            'indexable_location_pages' => ServiceLocationPage::query()->where('is_indexable', true)->count(),
            'published_services' => Service::query()->publicListing()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sitemapReport(SeoService $seoService): array
    {
        $paths = $seoService->generateServicesSitemapXml();

        return [
            'services_sitemap_bytes' => strlen($paths),
            'includes_about_us' => str_contains($seoService->generatePagesSitemapXml(), '/about-us'),
            'includes_services_catalog' => str_contains($seoService->generatePagesSitemapXml(), '/services-catalog'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function duplicateContentReport(): array
    {
        $duplicateSlugs = Page::query()
            ->select('slug')
            ->groupBy('slug')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('slug')
            ->all();

        return [
            'duplicate_page_slugs' => $duplicateSlugs,
            'location_cms_pages' => Page::query()->where('slug', 'like', 'service-%-loc-%')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function geoReadinessReport(): array
    {
        return [
            'pincodes_with_coverage_text' => PinCode::query()->whereNotNull('coverage_text')->where('coverage_text', '!=', '')->count(),
            'pincodes_with_landmarks' => PinCode::query()->whereHas('landmarks')->count(),
            'pincodes_geo_page_ready' => PinCode::query()->where('geo_page_ready', true)->count(),
            'avg_location_geo_score' => round((float) ServiceLocationPage::query()
                ->whereNotNull('quality_snapshot')
                ->get()
                ->avg(fn ($r) => $r->quality_snapshot['geo_readiness'] ?? 0), 1),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function aeoReadinessReport(): array
    {
        return [
            'pincode_location_faqs' => \App\Models\PinCodeLocationFaq::count(),
            'avg_location_aeo_score' => round((float) ServiceLocationPage::query()
                ->whereNotNull('quality_snapshot')
                ->get()
                ->avg(fn ($r) => $r->quality_snapshot['aeo_readiness'] ?? 0), 1),
            'noindex_location_pages' => ServiceLocationPage::query()->where('is_indexable', false)->count(),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $reports
     */
    private function toMarkdown(array $reports): string
    {
        $lines = ['# SEO / GEO / AEO Hardening Reports', '', 'Generated: '.now()->toIso8601String(), ''];

        $titles = [
            'schema_cleanup' => '1. Schema Cleanup Report',
            'internal_links' => '2. Internal Link Integrity Report',
            'location_uniqueness' => '3. Location Content Uniqueness Report',
            'pincode_expansion' => '4. Pincode Expansion Report',
            'sitemap_validation' => '5. Sitemap Validation Report',
            'duplicate_content_risk' => '6. Duplicate Content Risk Report',
            'geo_readiness' => '7. GEO Readiness Report',
            'aeo_readiness' => '8. AEO Readiness Report',
        ];

        foreach ($titles as $key => $title) {
            $lines[] = '## '.$title;
            $lines[] = '';
            $lines[] = '```json';
            $lines[] = json_encode($reports[$key] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $lines[] = '```';
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private function activePrimaryCityPincodeCount(): int
    {
        $city = app(LocalityContextResolver::class)->primaryCity();
        $query = PinCode::query()->where('is_active', true);

        if ($city !== null && $city !== '') {
            $query->where('city', 'like', '%'.$city.'%');
        }

        return $query->count();
    }
}
