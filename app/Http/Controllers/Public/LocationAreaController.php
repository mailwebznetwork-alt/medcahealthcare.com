<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PinCode;
use App\Models\Service;
use App\Services\Public\PinCodeAreaResolver;
use App\Services\UserLocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationAreaController extends Controller
{
    public function __construct(
        private readonly PinCodeAreaResolver $areaResolver,
        private readonly UserLocationService $location,
    ) {}

    public function show(Request $request, string $slug): View|RedirectResponse
    {
        $pin = $this->areaResolver->resolve($slug);
        abort_if($pin === null, 404);

        $canonicalSlug = $this->areaResolver->routeSlugFor($pin);
        if ($slug !== $canonicalSlug) {
            return redirect()->route('public.locations.area', ['slug' => $canonicalSlug], 301);
        }

        if ($request->session()->get('medca.detected_pincode') !== $pin->pincode) {
            $this->location->rememberPincode($pin->pincode);
        }

        $pin->loadMissing(['landmarks', 'hospitals', 'locationFaqs', 'nearbyAreas']);

        $coverageAreas = PinCode::query()
            ->where('is_active', true)
            ->whereKeyNot($pin->id)
            ->orderBy('city')
            ->orderBy('pincode')
            ->get();

        $services = Service::query()
            ->localizedListing($pin->pincode)
            ->with(['seo', 'pincodes', 'categories', 'faqs'])
            ->get();

        $area = $pin->area_name ?: $pin->locality ?: $pin->city ?: $pin->pincode;

        return view('public.locations.area', [
            'pin' => $pin,
            'area' => $area,
            'services' => $services,
            'coverageAreas' => $coverageAreas,
            'canonicalUrl' => $this->areaResolver->publicUrlFor($pin),
            'breadcrumbs' => [
                ['label' => __('Locations'), 'url' => url('/locations')],
                ['label' => $area, 'url' => null],
            ],
        ]);
    }
}
