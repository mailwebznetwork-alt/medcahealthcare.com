<?php

namespace App\Http\Controllers\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\StoreSeoEntityRequest;
use App\Http\Requests\Growth\StoreSeoTechnicalRequest;
use App\Models\SeoEntity;
use App\Models\SeoTechnical;
use App\Services\Growth\SeoService;
use App\Services\Growth\SeoSitemapFileGenerator;
use App\Support\GrowthReadinessReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SeoController extends Controller
{
    public function __construct(
        private readonly SeoService $seoService,
        private readonly SeoSitemapFileGenerator $sitemapFiles,
    ) {}

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

        $cached = $this->sitemapFiles->readCached('sitemap.xml');
        $xml = $cached ?? $this->seoService->generateSitemapIndex();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    public function sitemapSegmentXml(string $segment): SymfonyResponse
    {
        if (! $this->seoService->isSitemapPubliclyAvailable()) {
            return response('', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $filename = 'sitemap-'.$segment.'.xml';
        $cached = $this->sitemapFiles->readCached($filename);
        if ($cached !== null) {
            return response($cached, 200, [
                'Content-Type' => 'application/xml; charset=UTF-8',
            ]);
        }

        $xml = match (true) {
            $segment === 'static-pages' => $this->seoService->generateStaticPagesSitemapXml(),
            $segment === 'pages' => $this->seoService->generatePagesSitemapXml(),
            $segment === 'blogs' => $this->seoService->generateBlogsSitemapXml(),
            $segment === 'services' => config('sitemap.paginated_enabled', true)
                ? $this->seoService->generateServiceDetailsSitemapXml()
                : $this->seoService->generateServicesSitemapXml(),
            $segment === 'categories' => $this->seoService->generateCategoriesSitemapXml(),
            $segment === 'subservices' => $this->seoService->generateSubservicesSitemapXml(),
            $segment === 'images' => $this->seoService->generateImagesSitemapXml(),
            preg_match('/^locations-(\d{3})$/', $segment, $matches) === 1 => $this->seoService->generateLocationChunkSitemapXml((int) $matches[1]),
            default => null,
        };

        if ($xml === null) {
            return response('', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
