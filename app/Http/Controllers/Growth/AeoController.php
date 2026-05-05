<?php

namespace App\Http\Controllers\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\StoreAeoRequest;
use App\Models\SeoAiSignal;
use App\Services\Growth\AeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AeoController extends Controller
{
    public function __construct(private readonly AeoService $aeoService) {}

    public function index(Request $request): RedirectResponse|Response
    {
        if ($request->expectsJson()) {
            return response(['data' => SeoAiSignal::query()->latest('id')->first()]);
        }

        return redirect()->route('growth-center.competitors.index', ['tab' => 'aeo']);
    }

    public function store(StoreAeoRequest $request): RedirectResponse
    {
        $this->aeoService->saveSignals($request->validated());

        return redirect()->route('growth-center.competitors.index', ['tab' => 'aeo'])
            ->with('status', __('AEO signals saved.'));
    }

    public function llmTxt(): SymfonyResponse
    {
        return response($this->aeoService->generateLlmTxt(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function discovery(): Response
    {
        return response($this->aeoService->generateDiscoveryData());
    }
}
