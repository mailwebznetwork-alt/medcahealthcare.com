<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use App\Services\Discovery\RelatedContentEngine;
use App\Services\Operations\ServiceDetailPageProvisioner;
use App\Services\Operations\ServiceInternalLinkingEngine;
use App\Services\Operations\SubServicePageProvisioner;
use App\Services\Operations\ServicePublicUrlBuilder;
use App\Services\Public\PageRenderContextRegistrar;
use App\Services\Public\PublicDisplayNameResolver;
use App\Services\Public\ServicesDetailPageResolver;
use App\Services\ServiceContextCollector;
use App\Services\UserLocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServicePublicController extends Controller
{
    public function __construct(
        private readonly PageRenderContextRegistrar $pageRenderContext,
        private readonly ServicesDetailPageResolver $detailPageResolver,
        private readonly ServiceDetailPageProvisioner $detailPageProvisioner,
        private readonly UserLocationService $location,
        private readonly ServicePublicUrlBuilder $urlBuilder,
        private readonly ServiceInternalLinkingEngine $internalLinks,
        private readonly RelatedContentEngine $relatedContent,
        private readonly SubServicePageProvisioner $subServicePageProvisioner,
        private readonly PublicDisplayNameResolver $displayNames,
    ) {}

    public function showSubService(Request $request, string $code, string $subCode): View
    {
        $service = Service::findPubliclyViewableByCode($code);
        abort_if($service === null, 404);

        $sub = SubService::query()
            ->where('service_id', $service->id)
            ->where('sub_service_code', $subCode)
            ->publicListing()
            ->first();

        abort_if($sub === null, 404);

        return $this->renderSubServiceDetail($request, $service, $sub);
    }

    public function index(Request $request): View
    {
        $pincode = $this->location->currentPincode();
        $locationRequired = $pincode === null || $request->attributes->get('services_blocked_until_pincode') === true;

        $categories = $locationRequired
            ? collect()
            : app(\App\Services\Public\PublicPagePresenter::class)->localizedCategories($pincode, limit: 0);

        return view('public.services.index', [
            'categories' => $categories,
            'pincode' => $pincode,
            'locationRequired' => $locationRequired,
            'pinCodeRecord' => $this->location->currentPinCodeRecord(),
        ]);
    }

    public function show(Request $request, string $code): View
    {
        $service = Service::findPubliclyViewableByCode($code);

        abort_if($service === null, 404);

        return $this->renderServiceDetail($request, $service);
    }

    public function showLocation(Request $request, string $code, string $locationSlug): View
    {
        $service = Service::findPubliclyViewableByCode($code);
        abort_if($service === null, 404);

        $mapping = ServiceLocationPage::query()
            ->where('service_id', $service->id)
            ->where('location_slug', $locationSlug)
            ->with(['pincode', 'page'])
            ->first();

        abort_if($mapping === null || $mapping->page === null, 404);

        return $this->renderServiceLocation($request, $service, $mapping);
    }

    public function showLocationPincode(Request $request, string $code, string $city, string $pincode): View|RedirectResponse
    {
        $service = Service::findPubliclyViewableByCode($code);
        abort_if($service === null, 404);

        $normalized = preg_replace('/\D/', '', $pincode) ?? '';
        abort_if(strlen($normalized) !== 6, 404);

        $mapping = ServiceLocationPage::query()
            ->where('service_id', $service->id)
            ->whereHas('pincode', fn ($q) => $q->where('pincode', $normalized))
            ->with(['pincode', 'page'])
            ->first();

        abort_if($mapping === null || $mapping->page === null, 404);

        $expectedCity = $mapping->city_slug ?: $this->urlBuilder->citySlugForPin($mapping->pincode);
        if ($this->urlBuilder->slugify($city) !== $expectedCity) {
            return redirect($mapping->publicUrl(), 301);
        }

        return $this->renderServiceLocation($request, $service, $mapping);
    }

    private function renderServiceDetail(Request $request, Service $service): View
    {
        $pincode = $this->location->currentPincode();
        if ($pincode !== null && ! $service->isAvailableInPincode($pincode)) {
            abort(404);
        }

        $service->loadMissing(['seo', 'faqs', 'pincodes', 'detailPage', 'approvedReviews']);

        app(ServiceContextCollector::class)->register($service);

        $detailPage = $this->detailPageResolver->resolveFor($service);

        if ($detailPage === null) {
            try {
                $detailPage = $this->detailPageProvisioner->provision($service);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $internalLinks = $this->relatedContent->buildForService($service);

        if ($detailPage !== null) {
            $detailPage->loadMissing('faqs');
            $this->pageRenderContext->registerServiceDetail($detailPage, $service, [
                'breadcrumbs' => $this->serviceBreadcrumbs($service),
                'internalLinks' => $internalLinks,
            ]);

            return view('layouts.app', [
                'page' => $detailPage,
                'service' => $service,
            ]);
        }

        return view('public.services.show', [
            'service' => $service,
            'averageRating' => $service->averageApprovedRating(),
            'reviewsCount' => $service->approvedReviewsCount(),
            'breadcrumbs' => $this->serviceBreadcrumbs($service),
            'internalLinks' => $internalLinks,
        ]);
    }

    private function renderServiceLocation(Request $request, Service $service, ServiceLocationPage $mapping): View
    {
        $pincode = $mapping->pincode;
        abort_if($pincode === null, 404);

        if (! $service->isAvailableInPincode($pincode->pincode)) {
            abort(404);
        }

        $service->loadMissing(['seo', 'faqs', 'pincodes', 'approvedReviews']);
        $page = $mapping->page;
        abort_if($page === null || ! $page->is_active, 404);

        app(ServiceContextCollector::class)->register($service);

        $internalLinks = $this->relatedContent->buildForService($service);

        $this->pageRenderContext->registerServiceLocation($page, $service, $mapping, [
            'breadcrumbs' => $this->locationBreadcrumbs($service, $mapping),
            'internalLinks' => $internalLinks,
        ]);

        return view('layouts.app', [
            'page' => $page,
            'service' => $service,
            'serviceLocation' => $mapping,
        ]);
    }

    private function renderSubServiceDetail(Request $request, Service $service, SubService $sub): View
    {
        $pincode = $this->location->currentPincode();
        if ($pincode !== null && ! $service->isAvailableInPincode($pincode)) {
            abort(404);
        }

        $sub->loadMissing(['seo', 'faqs', 'service']);
        $page = $sub->linkedPage;

        if ($page === null && config('phase2_discovery.auto_sync_sub_service_pages', true)) {
            try {
                $page = $this->subServicePageProvisioner->syncFromSubService($sub);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        abort_if($page === null || ! $page->is_active, 404);

        $internalLinks = $this->relatedContent->persistSubService($sub);

        $this->pageRenderContext->registerSubServiceDetail($page, $sub, [
            'breadcrumbs' => $this->subServiceBreadcrumbs($service, $sub),
            'internalLinks' => $internalLinks,
        ]);

        return view('layouts.app', [
            'page' => $page,
            'service' => $service,
            'subService' => $sub,
        ]);
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    private function subServiceBreadcrumbs(Service $service, SubService $sub): array
    {
        return [
            ['label' => __('Home'), 'url' => url('/')],
            ['label' => __('Services'), 'url' => url('/services-catalog')],
            ['label' => $this->displayNames->serviceHeadline($service), 'url' => $service->publicUrl()],
            ['label' => $this->displayNames->subServiceHeadline($sub), 'url' => app(\App\Services\Discovery\Expansion\SeoExpansionEngine::class)->subServicePublicUrl($sub)],
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    private function serviceBreadcrumbs(Service $service): array
    {
        return [
            ['label' => __('Home'), 'url' => url('/')],
            ['label' => __('Services'), 'url' => url('/services-catalog')],
            ['label' => $this->displayNames->serviceHeadline($service), 'url' => $service->publicUrl()],
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    private function locationBreadcrumbs(Service $service, ServiceLocationPage $mapping): array
    {
        $mapping->loadMissing('pincode');
        $locationLabel = $mapping->pincode !== null
            ? $this->displayNames->locationHeadline($service, $mapping->pincode)
            : $this->displayNames->serviceHeadline($service);

        return [
            ['label' => __('Home'), 'url' => url('/')],
            ['label' => __('Services'), 'url' => url('/services-catalog')],
            ['label' => $this->displayNames->serviceHeadline($service), 'url' => $service->publicUrl()],
            ['label' => $locationLabel, 'url' => $mapping->publicUrl()],
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Service>
     */
    public function localizedServicesQuery(?string $pincode): \Illuminate\Database\Eloquent\Builder
    {
        return Service::query()
            ->localizedListing($pincode)
            ->with(['seo']);
    }
}
