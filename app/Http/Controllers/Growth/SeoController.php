<?php

namespace App\Http\Controllers\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\StoreSeoEntityRequest;
use App\Http\Requests\Growth\StoreSeoTechnicalRequest;
use App\Models\SeoEntity;
use App\Models\SeoTechnical;
use App\Services\Growth\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

        return redirect()->route('growth-center.competitors.index', ['tab' => 'seo'])
            ->with('status', __('SEO technical settings saved.'));
    }
}
