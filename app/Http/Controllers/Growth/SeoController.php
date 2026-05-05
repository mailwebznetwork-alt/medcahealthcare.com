<?php

namespace App\Http\Controllers\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\StoreSeoEntityRequest;
use App\Http\Requests\Growth\StoreSeoTechnicalRequest;
use App\Models\SeoEntity;
use App\Models\SeoTechnical;
use App\Services\Growth\SeoService;
use App\Support\GrowthReadinessReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SeoController extends Controller
{
    public function __construct(private readonly SeoService $seoService) {}

    public function entity(Request $request): RedirectResponse|Response
    {
        if ($request->expectsJson()) {
            return response([
                'data' => SeoEntity::query()->latest('id')->first(),
            ]);
        }

        return redirect()->route('growth-center.competitors.index', ['tab' => 'seo']);
    }

    public function storeEntity(StoreSeoEntityRequest $request): RedirectResponse
    {
        $this->seoService->saveEntity($request->validated());
        GrowthReadinessReport::forget();

        return redirect()->route('growth-center.competitors.index', ['tab' => 'seo'])
            ->with('status', __('SEO entity settings saved.'));
    }

    public function technical(Request $request): RedirectResponse|Response
    {
        if ($request->expectsJson()) {
            return response([
                'data' => SeoTechnical::query()->latest('id')->first(),
            ]);
        }

        return redirect()->route('growth-center.competitors.index', ['tab' => 'seo']);
    }

    public function storeTechnical(StoreSeoTechnicalRequest $request): RedirectResponse
    {
        $this->seoService->saveTechnical($request->validated());
        GrowthReadinessReport::forget();

        return redirect()->route('growth-center.competitors.index', ['tab' => 'seo'])
            ->with('status', __('SEO technical settings saved.'));
    }

    public function robotsTxt(): SymfonyResponse
    {
        return response($this->seoService->generateRobots(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemapXml(): SymfonyResponse
    {
        if (! $this->seoService->isSitemapPubliclyAvailable()) {
            return response('', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return response($this->seoService->generateSitemap(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
