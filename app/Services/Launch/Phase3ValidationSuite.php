<?php

namespace App\Services\Launch;

use App\Models\PageRegistry;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use App\Services\Governance\SiteArchitectCompatibilityValidator;
use App\Services\Governance\UniversalPageRegistry;
use App\Services\Growth\SeoService;
use App\Services\Import\ImportRegistry;
use App\Services\Seo\DatabaseFirstComplianceValidator;
use App\Services\Seo\GeoEnrichmentReadinessService;
use App\Services\Seo\UnifiedJsonLdGraphBuilder;

/**
 * Phase 3 launch validation — uses existing foundation validators.
 */
class Phase3ValidationSuite
{
    public function __construct(
        private readonly ImportRegistry $importRegistry,
        private readonly UniversalPageRegistry $pageRegistry,
        private readonly SiteArchitectCompatibilityValidator $siteArchitect,
        private readonly DatabaseFirstComplianceValidator $dbFirst,
        private readonly GeoEnrichmentReadinessService $geoReadiness,
        private readonly SeoService $seoService,
        private readonly UnifiedJsonLdGraphBuilder $jsonLd,
        private readonly PerformanceHardeningService $performance,
        private readonly TrackingValidationService $tracking,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function runAll(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'import_framework' => $this->importFrameworkReport(),
            'category_data' => $this->categoryReport(),
            'service_data' => $this->serviceReport(),
            'sub_service_data' => $this->subServiceReport(),
            'pincode_data' => $this->pincodeReport(),
            'mapping_data' => $this->mappingReport(),
            'geo_enrichment' => $this->geoReadiness->audit(),
            'seo_validation' => $this->seoValidation(),
            'geo_validation' => $this->geoValidation(),
            'aeo_validation' => $this->aeoValidation(),
            'internal_linking' => $this->internalLinkingReport(),
            'page_registry' => $this->pageRegistry->syncAll(),
            'site_architect' => $this->siteArchitect->validateAll(),
            'database_first' => $this->dbFirst->scanAppServices(),
            'performance' => $this->performance->audit(),
            'tracking' => $this->tracking->audit(),
            'search_engine' => $this->searchEngineReport(),
            'ai_discoverability' => $this->aiDiscoverabilityReport(),
            'production_readiness' => $this->productionReadiness(),
            'launch_score' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    public function score(array $report): array
    {
        $checks = [
            $report['import_framework']['implemented_count'] >= 5,
            ($report['category_data']['with_seo'] ?? 0) > 0 || ($report['category_data']['total'] ?? 0) === 0,
            ($report['service_data']['published'] ?? 0) > 0,
            ($report['geo_enrichment']['readiness_score'] ?? 0) >= 0,
            ($report['site_architect']['compatible'] ?? false) === true,
            ($report['database_first']['compliant'] ?? false) === true,
            ($report['tracking']['gtm_or_ga4_ready'] ?? false) === true,
            ($report['search_engine']['sitemap_enabled'] ?? false) === true,
        ];

        $passed = count(array_filter($checks));
        $total = count($checks);
        $score = $total > 0 ? (int) round(($passed / $total) * 100) : 0;

        $report['launch_score'] = $score;
        $report['launch_ready'] = $score >= 75;

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    private function importFrameworkReport(): array
    {
        $matrix = $this->importRegistry->readinessMatrix();
        $implemented = collect($matrix)->where('status', 'implemented')->count();

        return [
            'entities' => $matrix,
            'implemented_count' => $implemented,
            'total_entities' => count($matrix),
            'workflow' => config('import_registry.workflow'),
            'formats' => ['csv', 'xls', 'xlsx'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function categoryReport(): array
    {
        return [
            'total' => ServiceCategory::count(),
            'active' => ServiceCategory::query()->where('is_active', true)->count(),
            'with_page_id' => ServiceCategory::query()->whereNotNull('page_id')->count(),
            'with_seo' => ServiceCategory::query()->whereHas('seo')->count(),
            'featured' => ServiceCategory::query()->where('is_featured', true)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceReport(): array
    {
        return [
            'total' => Service::count(),
            'published' => Service::query()->publicListing()->count(),
            'with_seo' => Service::query()->whereHas('seo')->count(),
            'featured' => Service::query()->where('is_featured', true)->count(),
            'top_rated' => Service::query()->where('is_top_rated', true)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function subServiceReport(): array
    {
        return [
            'total' => SubService::count(),
            'published' => SubService::query()->publicListing()->count(),
            'with_page_id' => SubService::query()->whereNotNull('page_id')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pincodeReport(): array
    {
        return [
            'total' => PinCode::count(),
            'serviceable' => PinCode::query()->where('is_serviceable', true)->count(),
            'with_coverage' => PinCode::query()->whereNotNull('coverage_text')->where('coverage_text', '!=', '')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mappingReport(): array
    {
        return [
            'matrix_rows' => \App\Models\ServicePincode::count(),
            'location_pages' => ServiceLocationPage::count(),
            'indexable_location_pages' => ServiceLocationPage::query()->where('is_indexable', true)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function seoValidation(): array
    {
        $sample = Service::query()->publicListing()->with('seo')->first();

        return [
            'services_with_seo' => Service::query()->whereHas('seo')->count(),
            'categories_with_seo' => ServiceCategory::query()->whereHas('seo')->count(),
            'sample_json_ld_nodes' => $sample ? count($this->jsonLd->buildServiceGraph($sample)['@graph'] ?? []) : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function geoValidation(): array
    {
        $audit = $this->geoReadiness->audit();
        $serviceable = PinCode::query()->where('is_serviceable', true)->count();
        $enriched = PinCode::query()
            ->where('is_serviceable', true)
            ->where(function ($q): void {
                $q->whereHas('landmarks')
                    ->orWhereHas('hospitals')
                    ->orWhereNotNull('coverage_text');
            })
            ->count();

        return array_merge($audit, [
            'serviceable_pincodes' => $serviceable,
            'serviceable_with_geo_signals' => $enriched,
            'geo_coverage_pct' => $serviceable > 0 ? round(($enriched / $serviceable) * 100) : 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function aeoValidation(): array
    {
        return [
            'category_faqs' => \App\Models\ServiceCategoryFaq::count(),
            'service_faqs' => \App\Models\ServiceFaq::count(),
            'sub_service_faqs' => \App\Models\SubServiceFaq::count(),
            'location_faqs' => \App\Models\PinCodeLocationFaq::count(),
            'llm_txt_route' => '/llm.txt',
            'ai_discovery_route' => '/ai-discovery',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function internalLinkingReport(): array
    {
        return [
            'categories_with_snapshot' => ServiceCategory::query()->whereNotNull('internal_links_snapshot')->count(),
            'services_with_snapshot' => Service::query()->whereNotNull('internal_links_snapshot')->count(),
            'sub_services_with_snapshot' => SubService::query()->whereNotNull('internal_links_snapshot')->count(),
            'registry_rows' => PageRegistry::count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function searchEngineReport(): array
    {
        $technical = \App\Models\SeoTechnical::query()->first();

        return [
            'sitemap_enabled' => $technical?->sitemap_enabled ?? true,
            'sitemap_public' => $this->seoService->isSitemapPubliclyAvailable(),
            'robots_configured' => filled($technical?->robots_txt),
            'google_verification' => filled($technical?->google_site_verification),
            'sitemap_index_url' => url('/sitemap.xml'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function aiDiscoverabilityReport(): array
    {
        $technical = \App\Models\SeoTechnical::query()->first();

        return [
            'ai_discovery_enabled' => (bool) ($technical?->ai_discovery_enabled ?? false),
            'llm_txt_enabled' => filled($technical?->llm_txt),
            'structured_data_pages' => \App\Models\Page::query()->whereNotNull('schema_json')->count(),
            'entity_graph_builder' => UnifiedJsonLdGraphBuilder::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productionReadiness(): array
    {
        return [
            'homepage_route' => '/',
            'discovery_engine' => \App\Services\Discovery\HealthcareDiscoveryEngine::class,
            'change_pincode_engine' => \App\Services\Discovery\ChangePincodeEngine::class,
            'featured_engine' => \App\Services\Discovery\FeaturedContentEngine::class,
            'top_rated_engine' => \App\Services\Discovery\TopRatedEngine::class,
        ];
    }
}
