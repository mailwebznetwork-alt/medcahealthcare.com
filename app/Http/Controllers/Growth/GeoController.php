<?php

namespace App\Http\Controllers\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\StoreGeoRequest;
use App\Http\Requests\Growth\StorePincodeRequest;
use App\Models\GeoLocation;
use App\Models\Pincode;
use App\Services\Growth\GeoService;
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

        return redirect()->route('growth-center.competitors.index', ['tab' => 'geo']);
    }

    public function storeLocation(StoreGeoRequest $request): RedirectResponse
    {
        $this->geoService->saveLocation($request->validated());

        return redirect()->route('growth-center.competitors.index', ['tab' => 'geo'])
            ->with('status', __('Geo location saved.'));
    }

    public function pincodes(Request $request): RedirectResponse|Response
    {
        if ($request->expectsJson()) {
            return response(['data' => Pincode::query()->latest('id')->limit(100)->get()]);
        }

        return redirect()->route('growth-center.competitors.index', ['tab' => 'geo']);
    }

    public function storePincode(StorePincodeRequest $request): RedirectResponse
    {
        $this->geoService->addPincode($request->validated());

        return redirect()->route('growth-center.competitors.index', ['tab' => 'geo'])
            ->with('status', __('Pincode added.'));
    }

    public function updatePincode(StorePincodeRequest $request, int $id): RedirectResponse
    {
        $this->geoService->updatePincode($id, $request->validated());

        return redirect()->route('growth-center.competitors.index', ['tab' => 'geo'])
            ->with('status', __('Pincode updated.'));
    }
}
