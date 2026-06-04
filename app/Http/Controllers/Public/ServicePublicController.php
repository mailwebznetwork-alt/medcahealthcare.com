<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\Operations\ServiceDetailPageProvisioner;
use App\Services\Public\PageRenderContextRegistrar;
use App\Services\Public\ServicesDetailPageResolver;
use App\Services\ServiceContextCollector;
use App\Services\UserLocationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServicePublicController extends Controller
{
    public function __construct(
        private readonly PageRenderContextRegistrar $pageRenderContext,
        private readonly ServicesDetailPageResolver $detailPageResolver,
        private readonly ServiceDetailPageProvisioner $detailPageProvisioner,
        private readonly UserLocationService $location,
    ) {}

    public function index(Request $request): View
    {
        $pincode = $this->location->currentPincode();
        $locationRequired = $pincode === null || $request->attributes->get('services_blocked_until_pincode') === true;

        $services = $locationRequired
            ? collect()
            : $this->localizedServicesQuery($pincode)->get();

        return view('public.services.index', [
            'services' => $services,
            'pincode' => $pincode,
            'locationRequired' => $locationRequired,
            'pinCodeRecord' => $this->location->currentPinCodeRecord(),
        ]);
    }

    public function show(Request $request, string $code): View
    {
        $service = Service::findPubliclyViewableByCode($code);

        abort_if($service === null, 404);

        $pincode = $this->location->currentPincode();
        if ($pincode !== null && ! $service->isAvailableInPincode($pincode)) {
            abort(404);
        }

        $service->loadMissing(['seo', 'faqs', 'pincodes', 'detailPage', 'approvedReviews']);

        app(ServiceContextCollector::class)->register($service);

        $detailPage = $this->detailPageResolver->resolveFor($service);

        if ($detailPage === null) {
            try {
                $detailPage = $this->detailPageProvisioner->provision($service);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($detailPage !== null) {
            $detailPage->loadMissing('faqs');
            $this->pageRenderContext->registerServiceDetail($detailPage, $service);

            return view('layouts.app', [
                'page' => $detailPage,
                'service' => $service,
            ]);
        }

        return view('public.services.show', [
            'service' => $service,
            'averageRating' => $service->averageApprovedRating(),
            'reviewsCount' => $service->approvedReviewsCount(),
        ]);
    }

    /**
     * Shared localized listing query for controllers and presenters.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Service>
     */
    public function localizedServicesQuery(?string $pincode): \Illuminate\Database\Eloquent\Builder
    {
        return Service::query()
            ->localizedListing($pincode)
            ->with(['seo', 'pincodes']);
    }
}
