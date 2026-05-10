<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\ServiceContextCollector;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServicePublicController extends Controller
{
    public function show(Request $request, string $code): View
    {
        $service = Service::findPublishedByCode($code);

        abort_if($service === null, 404);

        $service->loadMissing(['seo', 'faqs', 'pincodes', 'detailPage']);

        app(ServiceContextCollector::class)->register($service);

        $detailPage = $service->detailPage;
        if ($detailPage !== null && (bool) $detailPage->is_active) {
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
