<?php

namespace App\Services\Public;

use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use App\Services\Content\ContentRenderContext;
use App\Services\Deployment\StylePackResolver;

/**
 * Registers a consistent ContentRenderContext for all public page renders.
 */
class PageRenderContextRegistrar
{
    public function __construct(
        private readonly PublicPagePresenter $presenter,
        private readonly ContentRenderContext $renderContext,
        private readonly StylePackResolver $stylePacks,
    ) {}

    public function register(Page $page): void
    {
        $this->renderContext->set(array_merge(
            $this->presenter->variablesFor($page),
            $this->stylePacks->contextVariables($page),
            ['currentPage' => $page],
        ));
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function registerServiceDetail(Page $page, Service $service, array $extra = []): void
    {
        $this->renderContext->set(array_merge(
            $this->presenter->variablesFor($page),
            $this->stylePacks->contextVariables($page),
            ['currentPage' => $page],
            $this->presenter->variablesForServiceDetail($service),
            $extra,
        ));
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function registerServiceLocation(Page $page, Service $service, ServiceLocationPage $mapping, array $extra = []): void
    {
        $this->renderContext->set(array_merge(
            $this->presenter->variablesFor($page),
            $this->stylePacks->contextVariables($page),
            ['currentPage' => $page],
            $this->presenter->variablesForServiceDetail($service),
            [
                'serviceLocation' => $mapping,
                'locationPincode' => $mapping->pincode,
            ],
            $extra,
        ));
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function registerCategoryDetail(Page $page, ServiceCategory $category, array $extra = []): void
    {
        $pincode = app(\App\Services\UserLocationService::class)->currentPincode();

        $this->renderContext->set(array_merge(
            $this->presenter->variablesFor($page),
            $this->stylePacks->contextVariables($page),
            ['currentPage' => $page],
            $this->presenter->variablesForCategoryDetail($category, $pincode),
            $extra,
        ));
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function registerSubServiceDetail(Page $page, SubService $sub, array $extra = []): void
    {
        $this->renderContext->set(array_merge(
            $this->presenter->variablesFor($page),
            $this->stylePacks->contextVariables($page),
            ['currentPage' => $page],
            $this->presenter->variablesForSubServiceDetail($sub),
            $extra,
        ));
    }
}
