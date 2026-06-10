<?php

namespace App\Services\Operations;

use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Repositories\Operations\ServiceCategoryRepository;
use App\Services\SiteArchitect\ServiceInsertCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CatalogFormViewData
{
    public function __construct(
        private readonly ServiceInsertCatalog $serviceInsertCatalog,
        private readonly ServiceCategoryRepository $categoryRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forCategory(Request $request, ServiceCategory $category): array
    {
        $category->loadMissing(['seo', 'faqs', 'schema', 'services', 'linkedPage']);
        $linkedPage = $category->linkedPage;

        return [
            'category' => $category,
            'service' => $category,
            'catalogKind' => 'category',
            'parentOptions' => app(ServiceCategoryRepository::class)->parentOptions($category->id),
            'linkedDetailPage' => $linkedPage,
            'patternDetailPage' => null,
            'suggestedDetailPageSlug' => 'category-'.$category->code,
            'detailPages' => $this->detailPagesForForm(),
            'pinCodes' => $this->pinCodesForForm(),
            'optimizationScores' => $this->optimizationScores($category),
            'seoRecommendations' => $this->seoRecommendations($category),
            'locationPageCount' => 0,
            'activeTab' => (string) $request->query('tab', old('active_tab', 'basic')),
            'categoryOptions' => $this->categoryRepository->activeForPicker(),
            'serviceCatalog' => $this->serviceInsertCatalog->forDropdown(),
            'selectedRelatedCodes' => [],
            'serviceReviews' => collect(),
            'subServices' => $category->services()->orderBy('services.sort_order')->orderBy('services.title')->get(),
            'managedModule' => null,
            'customFieldValues' => new \stdClass,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forSubService(Request $request, Service $parent, SubService $subService): array
    {
        $subService->loadMissing(['seo', 'faqs', 'schema', 'linkedPage', 'service.pincodes']);
        $linkedPage = $subService->linkedPage;

        return [
            'service' => $subService,
            'parentService' => $parent,
            'subService' => $subService,
            'catalogKind' => 'sub_service',
            'linkedDetailPage' => $linkedPage,
            'patternDetailPage' => null,
            'suggestedDetailPageSlug' => $parent->service_code.'-'.$subService->sub_service_code,
            'detailPages' => $this->detailPagesForForm(),
            'pinCodes' => $parent->pincodes,
            'optimizationScores' => $this->optimizationScores($subService),
            'seoRecommendations' => $this->seoRecommendations($subService),
            'locationPageCount' => 0,
            'activeTab' => (string) $request->query('tab', old('active_tab', 'basic')),
            'categoryOptions' => $this->categoryRepository->activeForPicker(),
            'serviceCatalog' => $this->serviceInsertCatalog->forDropdown()
                ->filter(static fn (Service $row): bool => (int) $row->id !== (int) $parent->id)
                ->values(),
            'selectedRelatedCodes' => [],
            'serviceReviews' => collect(),
            'subServices' => collect(),
            'managedModule' => null,
            'customFieldValues' => new \stdClass,
        ];
    }

    /**
     * @return Collection<int, PinCode>
     */
    private function pinCodesForForm(): Collection
    {
        return PinCode::query()->orderBy('pincode')->orderBy('area_name')->get();
    }

    /**
     * @return Collection<int, Page>
     */
    private function detailPagesForForm(): Collection
    {
        return Page::query()->orderBy('title')->get(['id', 'title', 'slug']);
    }

    /**
     * @return array<string, int>
     */
    private function optimizationScores(ServiceCategory|SubService $entity): array
    {
        $snapshot = $entity->optimization_snapshot['scores'] ?? null;
        if (is_array($snapshot)) {
            return $snapshot;
        }

        $seo = $entity->seo;

        return [
            'seo' => $seo?->seo_score ?? 0,
            'aeo' => $seo?->aeo_score ?? 0,
            'geo' => $seo?->geo_score ?? 0,
            'schema' => $seo?->schema_health_score ?? 0,
            'content' => $seo?->content_quality_score ?? 0,
            'local' => $seo?->local_seo_score ?? 0,
            'image' => $seo?->image_seo_score ?? 0,
            'ai_discovery' => $seo?->ai_discovery_score ?? 0,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function seoRecommendations(ServiceCategory|SubService $entity): array
    {
        $fromSeo = $entity->seo?->seo_recommendations;
        if (is_array($fromSeo)) {
            return $fromSeo;
        }

        $fromSnapshot = $entity->optimization_snapshot['recommendations'] ?? [];

        return is_array($fromSnapshot) ? $fromSnapshot : [];
    }
}
