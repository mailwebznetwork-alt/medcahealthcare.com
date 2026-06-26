<?php

namespace App\Http\Controllers\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\StoreAeoRequest;
use App\Models\BusinessProfile;
use App\Models\SeoAiSignal;
use App\Models\SeoTechnical;
use App\Services\Growth\AeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AeoController extends Controller
{
    public function __construct(private readonly AeoService $aeoService) {}

    public function index(Request $request): RedirectResponse|Response
    {
        if ($request->expectsJson()) {
            return response(['data' => SeoAiSignal::query()->latest('id')->first()]);
        }

        return redirect()->route('growth-center.competitors.index', ['tab' => 'seo']);
    }

    public function store(StoreAeoRequest $request): RedirectResponse
    {
        $this->aeoService->saveSignals($request->validated());

        return redirect()->route('growth-center.competitors.index', ['tab' => 'seo'])
            ->with('status', __('AEO signals saved.'));
    }

    public function llmTxt(): SymfonyResponse
    {
        return response($this->aeoService->generateLlmTxt(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function llmsTxt(): SymfonyResponse
    {
        return response($this->aeoService->generateLlmsTxt(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function discovery(): JsonResponse|Response
    {
        if (! Schema::hasTable('seo_technical') || ! Schema::hasTable('business_profiles')) {
            return response()->json(['message' => __('Not available.')], 404);
        }

        $profile = BusinessProfile::query()->where('website', config('app.url'))->first()
            ?? BusinessProfile::query()->latest('id')->first();

        if (! $profile instanceof BusinessProfile) {
            return response()->json(['message' => __('Not available.')], 404);
        }

        $technical = SeoTechnical::query()->where('business_profile_id', $profile->id)->first();

        if (! $technical instanceof SeoTechnical || ! $technical->ai_discovery_enabled) {
            return response()->json(['message' => __('Not available.')], 404);
        }

        return response()->json($this->aeoService->generateDiscoveryData());
    }
}
