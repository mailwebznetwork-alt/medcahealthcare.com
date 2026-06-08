<?php

namespace App\Http\Controllers\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\StoreGeoRequest;
use App\Http\Requests\Growth\StorePincodeRequest;
use App\Models\GeoLocation;
use App\Models\PinCode;
use App\Services\Growth\GeoService;
use App\Support\GrowthReadinessReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GeoController extends Controller
{
    public function __construct(private readonly GeoService $geoService) {}

    public function location(Request $request): RedirectResponse|Response
    {
        if ($request->expectsJson()) {
            return response(['data' => GeoLocation::query()->latest('id')->first()]);
        }

        return redirect()->route('growth-center.competitors.index', ['tab' => 'seo']);
    }

    public function storeLocation(StoreGeoRequest $request): RedirectResponse
    {
        $this->geoService->saveLocation($request->validated());
        GrowthReadinessReport::forget();

        return redirect()->route('growth-center.competitors.index', ['tab' => 'seo'])
            ->with('status', __('Geo location saved.'));
    }

    public function pincodes(Request $request): RedirectResponse|Response
    {
        if ($request->expectsJson()) {
            return response(['data' => PinCode::query()->latest('id')->limit(100)->get()]);
        }

        return redirect()->route('growth-center.competitors.index', ['tab' => 'seo']);
    }

    public function storePincode(StorePincodeRequest $request): RedirectResponse
    {
        $pinCode = $this->geoService->addPincode($request->validated());
        GrowthReadinessReport::forget();

        if ($pinCode === null) {
            return redirect()->route('growth-center.competitors.index', ['tab' => 'seo'])
                ->with('error', __('This pincode cannot be added. It may have been permanently removed from Operations.'));
        }

        return redirect()->route('growth-center.competitors.index', ['tab' => 'seo'])
            ->with('status', __('Pincode added.'));
    }

    public function updatePincode(StorePincodeRequest $request, int $id): RedirectResponse
    {
        $this->geoService->updatePincode($id, $request->validated());
        GrowthReadinessReport::forget();

        return redirect()->route('growth-center.competitors.index', ['tab' => 'seo'])
            ->with('status', __('Pincode updated.'));
    }
}
