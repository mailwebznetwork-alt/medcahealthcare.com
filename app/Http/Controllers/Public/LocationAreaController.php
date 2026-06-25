<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Public\PinCodeAreaResolver;
use App\Services\Public\PublicDisplayNameResolver;
use App\Services\Public\PublicPagePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LocationAreaController extends Controller
{
    public function __construct(
        private readonly PinCodeAreaResolver $areaResolver,
        private readonly PublicDisplayNameResolver $displayNames,
    ) {}

    public function show(Request $request, string $slug): View|RedirectResponse
    {
        $pin = $this->areaResolver->resolve($slug);
        abort_if($pin === null, 404);

        $canonicalSlug = $this->areaResolver->routeSlugFor($pin);
        if ($slug !== $canonicalSlug) {
            return redirect()->route(
                'public.locations.area',
                ['slug' => $canonicalSlug] + $request->only(['category', 'service']),
                301
            );
        }

        $category = ServiceCategory::findActiveByCode((string) $request->query('category', ''));
        $contextService = $this->resolveContextService($request, $pin);

        $pin->loadMissing(['landmarks', 'hospitals', 'locationFaqs', 'nearbyAreas']);

        $coverageAreas = PinCode::query()
            ->where('is_active', true)
            ->whereKeyNot($pin->id)
            ->orderBy('state')
            ->orderBy('city')
            ->get();

        $services = $this->servicesForLocation($pin, $category, $contextService);

        $area = $pin->area_name ?: $pin->locality ?: $pin->city ?: __('International');
        $hasCategoryContext = $category instanceof ServiceCategory;
        $hasServiceContext = $contextService instanceof Service;

        [$title, $intro] = $this->headlinesForContext(
            $area,
            $pin,
            $category,
            $contextService,
            $hasCategoryContext,
            $hasServiceContext,
        );

        $breadcrumbs = [
            ['label' => __('Locations'), 'url' => url('/locations')],
        ];

        if ($hasCategoryContext) {
            $breadcrumbs[] = ['label' => $category->name, 'url' => $category->publicUrl()];
        } elseif ($hasServiceContext) {
            $breadcrumbs[] = ['label' => $contextService->title, 'url' => $contextService->publicUrl()];
        }

        $breadcrumbs[] = ['label' => $area, 'url' => null];

        return view('public.locations.area', [
            'pin' => $pin,
            'area' => $area,
            'category' => $category,
            'contextService' => $contextService,
            'services' => $services,
            'coverageAreas' => $coverageAreas,
            'canonicalUrl' => $this->canonicalUrlFor($pin, $category, $contextService),
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'intro' => $intro,
            'showNearYouBlock' => $hasCategoryContext || $hasServiceContext,
            'nearYouPayload' => app(PublicPagePresenter::class)->nearYouPayload(),
        ]);
    }

    /**
     * @return Collection<int, Service>
     */
    private function servicesForLocation(PinCode $pin, ?ServiceCategory $category, ?Service $contextService): Collection
    {
        $query = Service::query()
            ->publicListing()
            ->with(['seo', 'categories', 'faqs']);

        if ($category instanceof ServiceCategory) {
            $query->whereHas('categories', fn ($q) => $q->where('service_categories.id', $category->id));
        } elseif ($contextService instanceof Service) {
            $query->whereKey($contextService->id);
        }

        return $query->get();
    }

    private function resolveContextService(Request $request, PinCode $pin): ?Service
    {
        $code = trim((string) $request->query('service', ''));
        if ($code === '') {
            return null;
        }

        $service = Service::query()
            ->publicListing()
            ->where('service_code', $code)
            ->first();

        if ($service === null) {
            return null;
        }

        return $service->loadMissing(['seo', 'pincodes', 'categories', 'faqs']);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function headlinesForContext(
        string $area,
        PinCode $pin,
        ?ServiceCategory $category,
        ?Service $contextService,
        bool $hasCategoryContext,
        bool $hasServiceContext,
    ): array {
        if ($hasCategoryContext) {
            $categoryName = $this->displayNames->categoryHeadline($category);

            return [
                __(':category in :area', ['category' => $categoryName, 'area' => $area]),
                __('Professional :category services available in :area.', [
                    'category' => $categoryName,
                    'area' => $area,
                ]),
            ];
        }

        if ($hasServiceContext) {
            $serviceName = $this->displayNames->serviceHeadline($contextService);

            return [
                $this->displayNames->locationHeadline($contextService, $pin),
                __('Professional :service services available in :area.', [
                    'service' => $serviceName,
                    'area' => $area,
                ]),
            ];
        }

        return [
            __('Digital Growth Platform Services in :area', ['area' => $area]),
            __('Professional digital growth platform services available in :area.', [
                'area' => $area,
            ]),
        ];
    }

    private function canonicalUrlFor(PinCode $pin, ?ServiceCategory $category, ?Service $contextService): string
    {
        $url = $this->areaResolver->publicUrlFor($pin);

        return \App\Support\CoverageLinkContext::append($url, $category, $contextService);
    }
}
