<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\Content\ContentRenderContext;
use App\Services\Public\PublicPagePresenter;
use App\Services\Public\ServicesDetailPageResolver;
use App\Services\ServiceContextCollector;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServicePublicController extends Controller
{
    public function __construct(
        private readonly PublicPagePresenter $presenter,
        private readonly ContentRenderContext $renderContext,
        private readonly ServicesDetailPageResolver $detailPageResolver,
    ) {}

    public function show(Request $request, string $code): View
    {
        $service = Service::findPubliclyViewableByCode($code);

        abort_if($service === null, 404);

        $service->loadMissing(['seo', 'faqs', 'pincodes', 'detailPage']);

        app(ServiceContextCollector::class)->register($service);

        $detailPage = $this->detailPageResolver->resolveFor($service);

        if ($detailPage !== null) {
            $detailPage->loadMissing('faqs');
            $this->renderContext->set($this->presenter->variablesForServiceDetail($service));

            return view('layouts.app', [
                'page' => $detailPage,
                'service' => $service,
            ]);
        }

        return view('public.services.show', [
            'service' => $service,
        ]);
    }
}
