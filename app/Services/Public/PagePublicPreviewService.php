<?php

namespace App\Services\Public;

use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Content\ContentRenderContext;
use App\Services\Operations\ServiceDetailPageProvisioner;
use App\Services\Discovery\RelatedContentEngine;

class PagePublicPreviewService
{
    public function __construct(
        private readonly PublicPagePresenter $presenter,
        private readonly ContentRenderContext $renderContext,
        private readonly PageRenderContextRegistrar $pageRenderContext,
    ) {}

    /**
     * View data for Site Architect page preview (matches public rendering context).
     *
     * @return array{page: Page, service?: Service}
     */
    public function viewDataFor(Page $page): array
    {
        $page->loadMissing('faqs');

        $locationMapping = ServiceLocationPage::query()
            ->where('page_id', $page->id)
            ->with(['service', 'pincode'])
            ->first();

        if ($locationMapping?->service !== null && $locationMapping->pincode !== null) {
            $service = $locationMapping->service;
            $internalLinks = $service->internal_links_snapshot
                ?: app(RelatedContentEngine::class)->buildForService($service);

            $this->pageRenderContext->registerServiceLocation($page, $service, $locationMapping, [
                'internalLinks' => $internalLinks,
            ]);

            return [
                'page' => $page,
                'service' => $service,
                'serviceLocation' => $locationMapping,
            ];
        }

        $service = $this->resolveServiceForPage($page);

        if ($service !== null) {
            $this->renderContext->set($this->presenter->variablesForServiceDetail($service));

            return [
                'page' => $page,
                'service' => $service,
            ];
        }

        $this->pageRenderContext->register($page);

        return ['page' => $page];
    }

    private function resolveServiceForPage(Page $page): ?Service
    {
        $linked = Service::query()
            ->where('detail_page_id', $page->id)
            ->first();

        if ($linked !== null) {
            return $linked;
        }

        $code = ServiceDetailPageProvisioner::serviceCodeFromPageSlug($page->slug);

        if ($code === null) {
            return null;
        }

        return Service::query()->where('service_code', $code)->first();
    }

}
