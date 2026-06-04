<?php

namespace App\Services\Public;

use App\Models\Page;
use App\Models\Service;
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

    public function registerServiceDetail(Page $page, Service $service): void
    {
        $this->renderContext->set(array_merge(
            $this->presenter->variablesFor($page),
            $this->stylePacks->contextVariables($page),
            ['currentPage' => $page],
            $this->presenter->variablesForServiceDetail($service),
        ));
    }
}
