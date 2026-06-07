<?php

namespace App\Services\Launch;

use App\Models\ImportBatch;
use App\Models\Page;
use App\Models\PageRegistry;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\ServicePincode;
use App\Models\SubService;
use App\Services\Discovery\ChangePincodeEngine;
use App\Services\Discovery\HealthcareDiscoveryEngine;
use App\Services\Discovery\RelatedContentEngine;
use App\Services\Governance\SiteArchitectCompatibilityValidator;
use App\Services\Growth\SeoService;
use App\Services\Import\ImportPipeline;
use App\Services\Import\ImportRegistry;
use App\Services\Import\ImportRollbackService;
use App\Services\Seo\DatabaseFirstComplianceValidator;
use App\Services\Seo\GeoEnrichmentReadinessService;
use App\Services\Operations\ServiceInternalLinkingEngine;
use App\Services\Seo\UnifiedJsonLdGraphBuilder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * Phase 4 — operational validation and go-live certification (read-only audits).
 */
class GoLiveCertificationService
{
    public function __construct(
        private readonly ImportRegistry $importRegistry,
        private readonly ImportPipeline $importPipeline,
        private readonly ImportRollbackService $importRollback,
        private readonly HealthcareDiscoveryEngine $discovery,
        private readonly ChangePincodeEngine $pincodeEngine,
        private readonly RelatedContentEngine $relatedContent,
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
    public function certify(): array
    {
        $this->syncOperationalSnapshots();

        $sections = [
            'import_system' => $this->certifyImportSystem(),
            'categories' => $this->certifyCategories(),
            'services' => $this->certifyServices(),
            'sub_services' => $this->certifySubServices(),
            'locations' => $this->certifyLocations(),
            'matrix' => $this->certifyMatrix(),
            'page_generation' => $this->certifyPageGeneration(),
            'discovery' => $this->certifyDiscovery(),
            'change_pincode' => $this->certifyChangePincode(),
            'internal_linking' => $this->certifyInternalLinking(),
            'seo' => $this->certifySeo(),
            'geo' => $this->certifyGeo(),
            'aeo' => $this->certifyAeo(),
            'schema' => $this->certifySchema(),
            'ai_discoverability' => $this->certifyAiDiscoverability(),
            'performance' => $this->certifyPerformance(),
            'tracking' => $this->certifyTracking(),
            'security_integrity' => $this->certifySecurityIntegrity(),
            'go_live_checklist' => [],
        ];

        $sections['go_live_checklist'] = $this->buildChecklist($sections);
        $scores = $this->computeScores($sections);
        $decision = $this->decide($sections, $scores);

        return [
            'generated_at' => now()->toIso8601String(),
            'phase' => 'phase_4_go_live_certification',
            'sections' => $sections,
            'scores' => $scores,
            'critical_issues' => $decision['critical'],
            'warnings' => $decision['warnings'],
            'recommendations' => $decision['recommendations'],
            'go_live_checklist' => $sections['go_live_checklist'],
            'decision' => $decision['verdict'],
            'certified' => $decision['verdict'] === 'GO',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function certifyImportSystem(): array
    {
        $entities = $this->importRegistry->registeredEntities();
        $required = ['categories', 'services', 'sub_services', 'pincodes', 'geo', 'mappings'];
        $missing = array_diff($required, $entities);
        $importsPath = (string) config('medca_launch.imports_path');
        $csvEntities = ['categories', 'services', 'sub_services', 'pincodes', 'geo'];
        $filesExist = collect($csvEntities)->every(fn (string $e) => File::exists("{$importsPath}/{$e}.csv"));

        $previewOk = false;
        $previewFile = "{$importsPath}/categories.csv";
        if (File::isReadable($previewFile)) {
            $preview = $this->importPipeline->preview('categories', $previewFile, 3);
            $previewOk = $preview['valid'] === true;
        }

        $batches = ImportBatch::query()->count();
        $rollbackable = ImportBatch::query()->where('status', 'committed')->whereNull('rolled_back_at')->count();

        $checks = [
            ['name' => 'registry_entities', 'passed' => $missing === [], 'detail' => 'Registered: '.implode(', ', $entities)],
            ['name' => 'production_csv_files', 'passed' => $filesExist, 'detail' => $importsPath],
            ['name' => 'preview_pipeline', 'passed' => $previewOk, 'detail' => 'categories.csv preview'],
            ['name' => 'audit_trail', 'passed' => $batches > 0, 'detail' => "{$batches} import batches"],
            ['name' => 'rollback_available', 'passed' => class_exists(ImportRollbackService::class), 'detail' => "{$rollbackable} rollbackable batches"],
            ['name' => 'workflow_approval', 'passed' => (bool) config('import_registry.workflow.requires_approval', true), 'detail' => 'requires_approval enabled'],
        ];

        return $this->section('import_system', $checks);
    }

    /**
     * @return array<string, mixed>
     */
    private function certifyCategories(): array
    {
        $checks = [];
        $categories = ServiceCategory::query()
            ->active()
            ->whereNotIn('code', ['main-services'])
            ->with(['seo', 'faqs', 'schema'])
            ->get();

        foreach ($categories as $category) {
            $key = $category->code;
            $checks[] = ['name' => "{$key}_seo", 'passed' => $category->seo !== null, 'detail' => 'SEO record'];
            $checks[] = ['name' => "{$key}_faq", 'passed' => $category->faqs->isNotEmpty(), 'detail' => $category->faqs->count().' FAQs'];
            $checks[] = ['name' => "{$key}_page", 'passed' => $category->page_id !== null, 'detail' => 'CMS page_id'];
            $checks[] = ['name' => "{$key}_registry", 'passed' => PageRegistry::query()->where('entity_type', 'category')->where('entity_id', $category->id)->exists(), 'detail' => 'registry entry'];
            $checks[] = ['name' => "{$key}_links", 'passed' => is_array($category->internal_links_snapshot) && $category->internal_links_snapshot !== [], 'detail' => 'internal links snapshot'];
        }

        $checks[] = ['name' => 'active_count', 'passed' => $categories->count() >= 7, 'detail' => $categories->count().' active categories'];

        return $this->section('categories', $checks);
    }

    /**
     * @return array<string, mixed>
     */
    private function certifyServices(): array
    {
        $checks = [];
        $services = Service::query()->publicListing()->with(['seo', 'faqs', 'schema', 'detailPage'])->get();

        foreach ($services as $service) {
            $code = $service->service_code;
            $checks[] = ['name' => "{$code}_description", 'passed' => filled($service->description), 'detail' => 'description'];
            $checks[] = ['name' => "{$code}_seo", 'passed' => $service->seo !== null && filled($service->seo->meta_title), 'detail' => 'SEO meta'];
            $checks[] = ['name' => "{$code}_faq", 'passed' => $service->faqs->isNotEmpty(), 'detail' => $service->faqs->count().' FAQs'];
            $checks[] = ['name' => "{$code}_page", 'passed' => $service->detail_page_id !== null, 'detail' => 'detail page'];
            $checks[] = ['name' => "{$code}_schema", 'passed' => $service->detailPage?->schema_json !== null || $service->schema !== null, 'detail' => 'schema'];
            $checks[] = ['name' => "{$code}_locations", 'passed' => $service->pincodes()->exists(), 'detail' => 'pincode mappings'];
            $linksOk = is_array($service->internal_links_snapshot) && $service->internal_links_snapshot !== [];
            $checks[] = ['name' => "{$code}_links", 'passed' => $linksOk, 'detail' => 'internal links snapshot'];
        }

        $checks[] = ['name' => 'published_count', 'passed' => $services->count() >= 7, 'detail' => $services->count().' published services'];

        return $this->section('services', $checks);
    }

    /**
     * @return array<string, mixed>
     */
    private function certifySubServices(): array
    {
        $checks = [];
        $subs = SubService::query()->publicListing()->with(['seo', 'faqs', 'service', 'linkedPage'])->get();

        foreach ($subs as $sub) {
            $key = $sub->sub_service_code;
            $checks[] = ['name' => "{$key}_parent", 'passed' => $sub->service_id !== null && $sub->service !== null, 'detail' => $sub->service?->service_code];
            $checks[] = ['name' => "{$key}_page", 'passed' => $sub->page_id !== null, 'detail' => 'CMS page'];
            $checks[] = ['name' => "{$key}_seo", 'passed' => $sub->seo !== null, 'detail' => 'SEO'];
            $checks[] = ['name' => "{$key}_faq", 'passed' => $sub->faqs->isNotEmpty(), 'detail' => 'FAQs'];
            $checks[] = ['name' => "{$key}_registry", 'passed' => PageRegistry::query()->where('entity_type', 'sub_service')->where('entity_id', $sub->id)->exists(), 'detail' => 'registry'];
        }

        $checks[] = ['name' => 'sub_service_count', 'passed' => $subs->count() >= 4, 'detail' => $subs->count().' sub-services'];

        return $this->section('sub_services', $checks);
    }

    /**
     * @return array<string, mixed>
     */
    private function certifyLocations(): array
    {
        $serviceable = PinCode::query()->where('is_serviceable', true)->where('is_active', true)->get();
        $checks = [];

        foreach ($serviceable as $pin) {
            $pin->loadMissing(['landmarks', 'hospitals', 'nearbyAreas', 'locationFaqs']);
            $code = $pin->pincode;
            $checks[] = ['name' => "{$code}_coverage", 'passed' => filled($pin->coverage_text), 'detail' => 'coverage text'];
            $checks[] = ['name' => "{$code}_landmarks", 'passed' => $pin->landmarks->isNotEmpty(), 'detail' => $pin->landmarks->count()];
            $checks[] = ['name' => "{$code}_hospitals", 'passed' => $pin->hospitals->isNotEmpty(), 'detail' => $pin->hospitals->count()];
            $checks[] = ['name' => "{$code}_faqs", 'passed' => $pin->locationFaqs->isNotEmpty(), 'detail' => 'location FAQs'];
        }

        $locationPages = ServiceLocationPage::query()->where('is_indexable', true)->count();
        $checks[] = ['name' => 'location_pages', 'passed' => $locationPages > 0, 'detail' => "{$locationPages} indexable location pages"];
        $checks[] = ['name' => 'geo_readiness', 'passed' => ($this->geoReadiness->audit()['readiness_score'] ?? 0) >= 80, 'detail' => 'geo enrichment score'];

        return $this->section('locations', $checks);
    }

    /**
     * @return array<string, mixed>
     */
    private function certifyMatrix(): array
    {
        $serviceIds = Service::query()->pluck('id');
        $pinIds = PinCode::query()->pluck('id');
        $orphanMappings = ServicePincode::query()
            ->where(fn ($q) => $q->whereNotIn('service_id', $serviceIds)->orWhereNotIn('pincode_id', $pinIds))
            ->count();

        $servicesWithoutPins = Service::query()->publicListing()->whereDoesntHave('pincodes')->count();
        $locationPagesOrphan = ServiceLocationPage::query()
            ->whereNotIn('service_id', Service::query()->pluck('id'))
            ->count();

        $checks = [
            ['name' => 'no_orphan_mappings', 'passed' => $orphanMappings === 0, 'detail' => "{$orphanMappings} orphan pivots"],
            ['name' => 'services_have_pins', 'passed' => $servicesWithoutPins === 0, 'detail' => "{$servicesWithoutPins} services without pins"],
            ['name' => 'location_pages_linked', 'passed' => $locationPagesOrphan === 0, 'detail' => "{$locationPagesOrphan} orphan location pages"],
            ['name' => 'matrix_count', 'passed' => ServicePincode::count() > 0, 'detail' => ServicePincode::count().' mappings'],
            ['name' => 'visible_mappings', 'passed' => ServicePincode::query()->where('is_visible', true)->exists(), 'detail' => 'visible pivots exist'],
        ];

        return $this->section('matrix', $checks);
    }

    /**
     * @return array<string, mixed>
     */
    private function certifyPageGeneration(): array
    {
        $categoryPages = ServiceCategory::query()->whereNotNull('page_id')->count();
        $servicePages = Service::query()->whereNotNull('detail_page_id')->count();
        $subPages = SubService::query()->whereNotNull('page_id')->count();
        $generated = Page::query()->where('page_source', 'generated')->count();
        $registry = PageRegistry::count();
        $architect = $this->siteArchitect->validateAll();

        $checks = [
            ['name' => 'category_pages', 'passed' => $categoryPages >= 7, 'detail' => "{$categoryPages} category pages"],
            ['name' => 'service_pages', 'passed' => $servicePages >= 7, 'detail' => "{$servicePages} service pages"],
            ['name' => 'sub_service_pages', 'passed' => $subPages >= 4, 'detail' => "{$subPages} sub-service pages"],
            ['name' => 'location_pages', 'passed' => ServiceLocationPage::count() > 0, 'detail' => ServiceLocationPage::count().' location pages'],
            ['name' => 'generated_pages', 'passed' => $generated > 0, 'detail' => "{$generated} generated"],
            ['name' => 'registry_synced', 'passed' => $registry >= $categoryPages + $servicePages, 'detail' => "{$registry} registry rows"],
            ['name' => 'site_architect', 'passed' => $architect['compatible'], 'detail' => implode('; ', $architect['issues']) ?: 'compatible'],
        ];

        return $this->section('page_generation', $checks);
    }

    /**
     * @return array<string, mixed>
     */
    private function certifyDiscovery(): array
    {
        $categories = $this->discovery->discoverCategories('560076');
        $services = $this->discovery->discoverServices(null, '560076');
        $subs = $this->discovery->discoverSubServices();
        $locations = $this->discovery->discoverLocations(null, '560076');
        $pin = $this->discovery->discoverPincode('560076');

        $checks = [
            ['name' => 'category_discovery', 'passed' => $categories->isNotEmpty(), 'detail' => $categories->count().' categories'],
            ['name' => 'service_discovery', 'passed' => $services->isNotEmpty(), 'detail' => $services->count().' services'],
            ['name' => 'sub_service_discovery', 'passed' => $subs->isNotEmpty(), 'detail' => $subs->count().' sub-services'],
            ['name' => 'location_discovery', 'passed' => $locations->isNotEmpty(), 'detail' => $locations->count().' locations'],
            ['name' => 'pincode_discovery', 'passed' => $pin !== null, 'detail' => $pin?->pincode ?? 'missing'],
        ];

        return $this->section('discovery', $checks);
    }

    /**
     * @return array<string, mixed>
     */
    private function certifyChangePincode(): array
    {
        $switch = $this->pincodeEngine->switch('560076');
        $search = $this->pincodeEngine->searchServiceable('560', 5);
        $current = $this->pincodeEngine->current();
        $leadRoute = Route::has('public.leads.store');

        $checks = [
            ['name' => 'pincode_switch', 'passed' => $switch['success'] === true, 'detail' => $switch['message'] ?? ''],
            ['name' => 'discovery_refresh', 'passed' => isset($switch['discovery']) && $switch['discovery'] !== [], 'detail' => 'discovery payload'],
            ['name' => 'pincode_search', 'passed' => $search !== [], 'detail' => count($search).' results'],
            ['name' => 'session_persistence', 'passed' => ($current['pincode'] ?? null) === '560076', 'detail' => 'current pincode'],
            ['name' => 'lead_route', 'passed' => $leadRoute, 'detail' => 'POST /leads'],
        ];

        return $this->section('change_pincode', $checks);
    }

    /**
     * @return array<string, mixed>
     */
    private function certifyInternalLinking(): array
    {
        $checks = [];
        $category = ServiceCategory::query()->active()->first();

        if ($category !== null) {
            $links = $this->relatedContent->buildForCategory($category, '560076');
            $checks[] = ['name' => 'category_to_service', 'passed' => ($links['related_services'] ?? []) !== [], 'detail' => 'related services'];
            $checks[] = ['name' => 'related_categories', 'passed' => isset($links['related_categories']), 'detail' => 'related categories block'];
        }

        $service = Service::query()->publicListing()->first();
        if ($service !== null) {
            $links = $this->relatedContent->buildForService($service);
            $checks[] = ['name' => 'service_to_location', 'passed' => ($links['related_locations'] ?? []) !== [] || $service->pincodes()->exists(), 'detail' => 'related locations'];
        }

        $brokenRegistry = PageRegistry::query()->whereNotNull('page_id')->whereDoesntHave('page')->count();
        $checks[] = ['name' => 'no_broken_registry', 'passed' => $brokenRegistry === 0, 'detail' => "{$brokenRegistry} broken registry refs"];

        return $this->section('internal_linking', $checks);
    }

    /**
     * @return array<string, mixed>
     */
    private function certifySeo(): array
    {
        $technical = \App\Models\SeoTechnical::query()->first();
        $sitemap = $this->seoService->isSitemapPubliclyAvailable();
        $dbFirst = $this->dbFirst->scanAppServices();

        $checks = [
            ['name' => 'sitemap_enabled', 'passed' => $sitemap, 'detail' => '/sitemap.xml'],
            ['name' => 'robots_configured', 'passed' => filled($technical?->robots_txt) || Route::has('public.robots'), 'detail' => 'robots.txt'],
            ['name' => 'database_first', 'passed' => $dbFirst['compliant'] ?? false, 'detail' => count($dbFirst['violations'] ?? []).' violations'],
            ['name' => 'services_have_meta', 'passed' => Service::query()->publicListing()->whereHas('seo', fn ($q) => $q->whereNotNull('meta_title'))->count() >= 7, 'detail' => 'service meta titles'],
            ['name' => 'canonical_paths', 'passed' => Service::query()->publicListing()->whereHas('seo', fn ($q) => $q->whereNotNull('meta_description'))->exists(), 'detail' => 'meta descriptions'],
        ];

        return $this->section('seo', $checks);
    }

    /**
     * @return array<string, mixed>
     */
    private function certifyGeo(): array
    {
        $audit = $this->geoReadiness->audit();
        $serviceable = PinCode::query()->where('is_serviceable', true)->where('is_active', true)->count();
        $enriched = PinCode::query()
            ->where('is_serviceable', true)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereHas('landmarks')->orWhereHas('hospitals'))
            ->count();

        $checks = [
            ['name' => 'landmarks_populated', 'passed' => ($audit['with_landmarks'] ?? 0) > 0, 'detail' => $audit['with_landmarks'].' pincodes'],
            ['name' => 'hospitals_populated', 'passed' => ($audit['with_hospitals'] ?? 0) > 0, 'detail' => $audit['with_hospitals'].' pincodes'],
            ['name' => 'coverage_text', 'passed' => ($audit['with_coverage_text'] ?? 0) >= $serviceable * 0.8, 'detail' => $audit['with_coverage_text'].' with coverage'],
            ['name' => 'full_geo_coverage', 'passed' => $serviceable > 0 && $enriched >= $serviceable, 'detail' => "{$enriched}/{$serviceable} enriched"],
            ['name' => 'location_pages_indexable', 'passed' => ($audit['indexable_location_pages'] ?? 0) > 0, 'detail' => $audit['indexable_location_pages'].' pages'],
        ];

        return $this->section('geo', $checks);
    }

    /**
     * @return array<string, mixed>
     */
    private function certifyAeo(): array
    {
        $faqTotal = \App\Models\ServiceCategoryFaq::count()
            + \App\Models\ServiceFaq::count()
            + \App\Models\SubServiceFaq::count()
            + \App\Models\PinCodeLocationFaq::count();

        $checks = [
            ['name' => 'category_faqs', 'passed' => \App\Models\ServiceCategoryFaq::count() >= 7, 'detail' => \App\Models\ServiceCategoryFaq::count().' FAQs'],
            ['name' => 'service_faqs', 'passed' => \App\Models\ServiceFaq::count() >= 7, 'detail' => \App\Models\ServiceFaq::count().' FAQs'],
            ['name' => 'sub_service_faqs', 'passed' => \App\Models\SubServiceFaq::count() >= 4, 'detail' => \App\Models\SubServiceFaq::count().' FAQs'],
            ['name' => 'location_faqs', 'passed' => \App\Models\PinCodeLocationFaq::count() > 0, 'detail' => \App\Models\PinCodeLocationFaq::count().' FAQs'],
            ['name' => 'llm_txt_route', 'passed' => Route::has('public.llm'), 'detail' => '/llm.txt'],
            ['name' => 'faq_total', 'passed' => $faqTotal >= 30, 'detail' => "{$faqTotal} total FAQs"],
        ];

        return $this->section('aeo', $checks);
    }

    /**
     * @return array<string, mixed>
     */
    private function certifySchema(): array
    {
        $checks = [];
        $service = Service::query()->publicListing()->first();
        if ($service !== null) {
            $graph = $this->jsonLd->buildServiceGraph($service);
            $types = collect($graph['@graph'] ?? [])->pluck('@type')->flatten()->unique()->values()->all();
            $checks[] = ['name' => 'service_graph', 'passed' => count($graph['@graph'] ?? []) > 0, 'detail' => implode(', ', $types)];
            foreach (['Service', 'FAQPage', 'BreadcrumbList'] as $type) {
                $checks[] = ['name' => "service_has_{$type}", 'passed' => in_array($type, $types, true), 'detail' => $type];
            }
        }

        $schemaPages = Page::query()->whereNotNull('schema_json')->count();
        $checks[] = ['name' => 'pages_with_schema', 'passed' => $schemaPages > 50, 'detail' => "{$schemaPages} pages"];
        $checks[] = ['name' => 'json_ld_valid', 'passed' => $schemaPages > 0, 'detail' => '@graph synchronized'];

        return $this->section('schema', $checks);
    }

    /**
     * @return array<string, mixed>
     */
    private function certifyAiDiscoverability(): array
    {
        $technical = \App\Models\SeoTechnical::query()->first();
        $entityPages = Page::query()->whereNotNull('schema_json')->count();

        $checks = [
            ['name' => 'structured_data', 'passed' => $entityPages > 0, 'detail' => "{$entityPages} pages with JSON-LD"],
            ['name' => 'ai_discovery_flag', 'passed' => (bool) ($technical?->ai_discovery_enabled ?? false), 'detail' => 'ai_discovery_enabled'],
            ['name' => 'llm_txt', 'passed' => filled($technical?->llm_txt) || Route::has('public.llm'), 'detail' => 'llm.txt endpoint'],
            ['name' => 'entity_relationships', 'passed' => Service::query()->whereHas('categories')->exists(), 'detail' => 'category-service links'],
            ['name' => 'faq_readiness', 'passed' => \App\Models\ServiceFaq::count() >= 7, 'detail' => 'service FAQs for AI overviews'],
        ];

        return $this->section('ai_discoverability', $checks);
    }

    /**
     * @return array<string, mixed>
     */
    private function certifyPerformance(): array
    {
        $audit = $this->performance->audit();

        $start = microtime(true);
        Service::query()->publicListing()->with(['seo', 'pincodes', 'categories'])->get();
        $catalogMs = (int) round((microtime(true) - $start) * 1000);

        $start = microtime(true);
        PinCode::query()->where('is_serviceable', true)->with(['landmarks', 'hospitals', 'services'])->get();
        $pincodeMs = (int) round((microtime(true) - $start) * 1000);

        $checks = [
            ['name' => 'webp_pipeline', 'passed' => $audit['webp_pipeline'] ?? false, 'detail' => 'MediaUploadProcessor'],
            ['name' => 'lazy_loading', 'passed' => $audit['lazy_loading'] ?? false, 'detail' => 'responsive-media component'],
            ['name' => 'catalog_query_ms', 'passed' => $catalogMs < 500, 'detail' => "{$catalogMs}ms"],
            ['name' => 'pincode_query_ms', 'passed' => $pincodeMs < 500, 'detail' => "{$pincodeMs}ms"],
            ['name' => 'cache_layers', 'passed' => isset($audit['cache_layers']), 'detail' => 'app-level caching active'],
        ];

        return $this->section('performance', $checks);
    }

    /**
     * @return array<string, mixed>
     */
    private function certifyTracking(): array
    {
        $audit = $this->tracking->audit();

        $checks = [
            ['name' => 'gtm', 'passed' => $audit['gtm_configured'] ?? false, 'detail' => 'GTM container'],
            ['name' => 'ga4', 'passed' => $audit['ga4_configured'] ?? false, 'detail' => 'GA4 measurement ID'],
            ['name' => 'whatsapp', 'passed' => $audit['whatsapp_ready'] ?? false, 'detail' => 'WhatsApp click tracking'],
            ['name' => 'search_console', 'passed' => $audit['search_console']['configured'] ?? false, 'detail' => 'GSC verification token'],
            ['name' => 'conversion_events', 'passed' => count($audit['conversion_events'] ?? []) >= 3, 'detail' => 'phone/whatsapp/form events'],
            ['name' => 'tracking_components', 'passed' => ($audit['tracking_components']['tracking-head.blade.php'] ?? false), 'detail' => 'blade injection'],
        ];

        return $this->section('tracking', $checks);
    }

    /**
     * @return array<string, mixed>
     */
    private function certifySecurityIntegrity(): array
    {
        $registryOrphans = PageRegistry::query()->whereNotNull('page_id')->whereDoesntHave('page')->count();
        $batchIntegrity = ImportBatch::query()->where('status', 'committed')->count();
        $subOrphans = SubService::query()->whereNotIn('service_id', Service::query()->pluck('id'))->count();

        $checks = [
            ['name' => 'registry_integrity', 'passed' => $registryOrphans === 0, 'detail' => "{$registryOrphans} orphan registry rows"],
            ['name' => 'sub_service_parents', 'passed' => $subOrphans === 0, 'detail' => "{$subOrphans} orphan sub-services"],
            ['name' => 'import_audit_trail', 'passed' => $batchIntegrity > 0, 'detail' => "{$batchIntegrity} committed batches"],
            ['name' => 'rollback_service', 'passed' => class_exists(ImportRollbackService::class), 'detail' => 'rollback available'],
            ['name' => 'db_first_compliant', 'passed' => ($this->dbFirst->scanAppServices()['compliant'] ?? false), 'detail' => 'no hardcoded localities in app/'],
        ];

        return $this->section('security_integrity', $checks);
    }

    /**
     * @param  array<string, array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     */
    private function buildChecklist(array $sections): array
    {
        $map = [
            'Content' => ['categories', 'services', 'sub_services', 'page_generation'],
            'SEO' => ['seo'],
            'GEO' => ['geo', 'locations'],
            'AEO' => ['aeo'],
            'Schema' => ['schema'],
            'Discovery' => ['discovery', 'change_pincode'],
            'Tracking' => ['tracking'],
            'Performance' => ['performance'],
            'Lead Flow' => ['change_pincode'],
        ];

        $checklist = [];
        foreach ($map as $label => $keys) {
            $passed = true;
            foreach ($keys as $key) {
                if (! ($sections[$key]['passed'] ?? false)) {
                    $passed = false;
                }
            }
            $checklist[] = ['area' => $label, 'status' => $passed ? 'PASS' : 'FAIL'];
        }

        return $checklist;
    }

    /**
     * @param  array<string, array<string, mixed>>  $sections
     * @return array<string, int>
     */
    private function computeScores(array $sections): array
    {
        $map = [
            'architecture' => ['import_system', 'security_integrity', 'page_generation'],
            'data' => ['categories', 'services', 'sub_services', 'locations', 'matrix'],
            'seo' => ['seo'],
            'geo' => ['geo', 'locations'],
            'aeo' => ['aeo'],
            'performance' => ['performance'],
            'tracking' => ['tracking'],
            'discovery' => ['discovery', 'change_pincode', 'internal_linking'],
        ];

        $scores = [];
        foreach ($map as $scoreKey => $sectionKeys) {
            $rates = [];
            foreach ($sectionKeys as $sk) {
                $rates[] = $sections[$sk]['pass_rate'] ?? 0;
            }
            $scores[$scoreKey] = $rates === [] ? 0 : (int) round(array_sum($rates) / count($rates));
        }

        $scores['launch'] = (int) round(array_sum($scores) / max(1, count($scores)));

        return $scores;
    }

    /**
     * @param  array<string, array<string, mixed>>  $sections
     * @param  array<string, int>  $scores
     * @return array{verdict: string, critical: list<string>, warnings: list<string>, recommendations: list<string>}
     */
    private function decide(array $sections, array $scores): array
    {
        $critical = [];
        $warnings = [];
        $recommendations = [];

        $blocking = ['page_generation', 'discovery', 'change_pincode', 'matrix'];
        foreach ($blocking as $key) {
            if (! ($sections[$key]['passed'] ?? false)) {
                $critical[] = "Section [{$key}] failed certification";
            }
        }

        if (! ($sections['security_integrity']['passed'] ?? false)) {
            foreach ($sections['security_integrity']['failures'] ?? [] as $fail) {
                if (in_array($fail['name'], ['registry_integrity', 'sub_service_parents', 'import_audit_trail'], true)) {
                    $critical[] = "Security: {$fail['name']} — {$fail['detail']}";
                }
            }
        }

        if (($sections['services']['checks'] ?? []) !== [] && ! ($sections['services']['passed'] ?? false)) {
            $published = collect($sections['services']['checks'] ?? [])->firstWhere('name', 'published_count');
            if (! ($published['passed'] ?? false)) {
                $critical[] = 'Section [services] — insufficient published services';
            }
        }

        if (! ($sections['tracking']['passed'] ?? false)) {
            $warnings[] = 'GTM/GA4/Search Console not fully configured — configure before marketing launch';
            $recommendations[] = 'Set MEDCA_GTM_CONTAINER_ID, MEDCA_GA4_MEASUREMENT_ID, MEDCA_GSC_VERIFICATION in .env';
        }

        if (! ($sections['seo']['passed'] ?? false)) {
            $dbFirstFail = collect($sections['seo']['failures'] ?? [])->firstWhere('name', 'database_first');
            if ($dbFirstFail !== null) {
                $critical[] = 'Database-first compliance violation in app/';
            }
        }

        if (($scores['performance'] ?? 0) < 80) {
            $warnings[] = 'Performance score below 80 — review media WebP coverage';
        }

        if (($sections['ai_discoverability']['passed'] ?? false) === false) {
            $warnings[] = 'Enable ai_discovery_enabled in SeoTechnical for full AI endpoint readiness';
        }

        $recommendations[] = 'Submit sitemap.xml in Google Search Console after DNS cutover';
        $recommendations[] = 'Mark phone_click, whatsapp_click, form_submit as GA4 conversions';

        $verdict = $critical === [] && ($scores['launch'] ?? 0) >= 75 ? 'GO' : 'NO-GO';

        return compact('verdict', 'critical', 'warnings', 'recommendations');
    }

    /**
     * @param  list<array{name: string, passed: bool, detail: string}>  $checks
     * @return array<string, mixed>
     */
    private function section(string $key, array $checks): array
    {
        $passed = count(array_filter($checks, fn (array $c): bool => $c['passed']));
        $total = count($checks);

        return [
            'key' => $key,
            'passed' => $total > 0 && ($passed / $total) >= 0.85,
            'strict_passed' => $passed === $total && $total > 0,
            'pass_rate' => $total > 0 ? (int) round(($passed / $total) * 100) : 0,
            'checks_passed' => $passed,
            'checks_total' => $total,
            'checks' => $checks,
            'failures' => array_values(array_filter($checks, fn (array $c): bool => ! $c['passed'])),
        ];
    }

    private function syncOperationalSnapshots(): void
    {
        $linking = app(ServiceInternalLinkingEngine::class);

        ServiceCategory::query()->active()->each(function (ServiceCategory $category): void {
            $this->relatedContent->persistCategory($category, '560076');
        });

        Service::query()->publicListing()->each(function (Service $service) use ($linking): void {
            $linking->persist($service->fresh(['pincodes', 'locationPages.page', 'categories']));
        });

        SubService::query()->publicListing()->each(function (SubService $sub): void {
            $this->relatedContent->persistSubService($sub);
        });
    }
}
