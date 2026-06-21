<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StorePublicLeadRequest;
use App\Services\Leads\LeadIngestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeadCaptureController extends Controller
{
    public function __construct(
        private readonly LeadIngestionService $ingestion,
    ) {}

    public function store(StorePublicLeadRequest $request): RedirectResponse
    {
        if ($request->filled('website')) {
            return back()->with('lead_status', __('Thank you. We will contact you shortly.'));
        }

        $result = $this->ingestion->ingest($request->validated(), $request);

        if ($result['status'] === 422) {
            return back()
                ->withInput()
                ->withErrors(['phone' => $result['body']['message'] ?? __('Invalid submission.')]);
        }

        $message = ($result['body']['duplicate'] ?? false)
            ? __('We already received your request recently. Our team will call you back.')
            : __('Thank you. A MarkOnMinds care advisor will contact you shortly.');

        return back()->with('lead_status', $message);
    }
}
