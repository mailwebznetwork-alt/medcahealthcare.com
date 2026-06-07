<?php

namespace App\Services\Launch;

use App\Models\Page;
use App\Models\PageRegistry;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceCategoryFaq;
use App\Models\ServiceFaq;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use App\Models\SubServiceFaq;
use App\Services\Discovery\ChangePincodeEngine;
use App\Services\Governance\SiteArchitectCompatibilityValidator;
use App\Services\Seo\GeoEnrichmentReadinessService;

class ProductionLaunchAuditService
{
    public function __construct(
        private readonly Phase3ValidationSuite $suite,
        private readonly GeoEnrichmentReadinessService $geoReadiness,
        private readonly SiteArchitectCompatibilityValidator $siteArchitect,
        private readonly ChangePincodeEngine $pincodeEngine,
        private readonly PerformanceHardeningService $performance,
        private readonly TrackingValidationService $tracking,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function audit(): array
    {
        $base = $this->suite->score($this->suite->runAll());
        $geo = $this->geoReadiness->audit();
        $serviceable = PinCode::query()->where('is_serviceable', true)->where('is_active', true)->count();
        $geoEnriched = PinCode::query()
            ->where('is_serviceable', true)
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->whereHas('landmarks')
                    ->orWhereHas('hospitals')
                    ->orWhere(fn ($q2) => $q2->whereNotNull('coverage_text')->where('coverage_text', '!=', ''));
            })
            ->count();

        $pincodeSwitch = $this->pincodeEngine->switch('560076');

        $scores = [
            'seo' => $this->scoreSeo($base),
            'geo' => $serviceable > 0 ? (int) round(($geoEnriched / $serviceable) * 100) : 0,
            'aeo' => $this->scoreAeo(),
            'performance' => $this->scorePerformance(),
            'tracking' => $this->scoreTracking(),
            'content' => $this->scoreContent(),
            'discovery' => $pincodeSwitch['success'] ? 100 : 0,
        ];

        $scores['launch'] = (int) round(array_sum($scores) / count($scores));

        return array_merge($base, [
            'totals' => [
                'categories' => ServiceCategory::count(),
                'services' => Service::count(),
                'sub_services' => SubService::count(),
                'pincodes' => PinCode::count(),
                'serviceable_pincodes' => $serviceable,
                'mappings' => \App\Models\ServicePincode::count(),
                'generated_pages' => Page::query()->where('page_source', 'generated')->count(),
                'faqs' => ServiceCategoryFaq::count() + ServiceFaq::count() + SubServiceFaq::count() + \App\Models\PinCodeLocationFaq::count(),
                'schema_pages' => Page::query()->whereNotNull('schema_json')->count(),
                'internal_link_snapshots' => ServiceCategory::query()->whereNotNull('internal_links_snapshot')->count()
                    + Service::query()->whereNotNull('internal_links_snapshot')->count()
                    + SubService::query()->whereNotNull('internal_links_snapshot')->count(),
                'location_pages' => ServiceLocationPage::count(),
                'registry_rows' => PageRegistry::count(),
            ],
            'scores' => $scores,
            'pincode_validation' => [
                'switch_success' => $pincodeSwitch['success'],
                'discovery_keys' => array_keys($pincodeSwitch['discovery'] ?? []),
                'session_pincode' => $pincodeSwitch['pincode'] ?? null,
            ],
            'geo_coverage' => [
                'serviceable' => $serviceable,
                'enriched' => $geoEnriched,
                'pct' => $scores['geo'],
            ],
            'site_architect' => $this->siteArchitect->validateAll(),
            'performance' => $this->performance->audit(),
            'tracking' => $this->tracking->audit(),
            'launch_ready' => $scores['launch'] >= 70
                && ($base['site_architect']['compatible'] ?? false)
                && Service::query()->publicListing()->count() > 0,
        ]);
    }

    private function scoreSeo(array $base): int
    {
        $services = Service::query()->whereHas('seo')->count();
        $total = max(1, Service::count());
        $categories = ServiceCategory::query()->whereHas('seo')->count();
        $catTotal = max(1, ServiceCategory::count());

        return (int) round((($services / $total) * 60) + (($categories / $catTotal) * 40));
    }

    private function scoreAeo(): int
    {
        $faqs = ServiceCategoryFaq::count() + ServiceFaq::count() + SubServiceFaq::count();

        return min(100, $faqs * 5);
    }

    private function scorePerformance(): int
    {
        $audit = $this->performance->audit();

        return ($audit['webp_pipeline'] ?? false) && ($audit['lazy_loading'] ?? false) ? 85 : 50;
    }

    private function scoreTracking(): int
    {
        $audit = $this->tracking->audit();
        $score = 0;
        if ($audit['gtm_or_ga4_ready'] ?? false) {
            $score += 50;
        }
        if ($audit['whatsapp_ready'] ?? false) {
            $score += 25;
        }
        if ($audit['search_console']['configured'] ?? false) {
            $score += 25;
        }

        return $score;
    }

    private function scoreContent(): int
    {
        $published = Service::query()->publicListing()->count();
        $subs = SubService::query()->publicListing()->count();
        $cats = ServiceCategory::query()->where('is_active', true)->count();

        return min(100, ($published * 10) + ($subs * 5) + ($cats * 5));
    }
}
