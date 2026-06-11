<?php

namespace App\Services\Marketing\Attribution;

use App\Models\Lead;
use App\Models\MarketingAttributionSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LeadAttributionService
{
    public function __construct(
        private readonly UtmCaptureService $utmCapture,
        private readonly LandingPageContextResolver $landingContextResolver,
        private readonly AttributionSessionPersister $sessionPersister,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function applyToLead(Lead $lead, array $validated, Request $request): void
    {
        if (! config('marketing_automation.enabled', true)) {
            return;
        }

        $attribution = $this->utmCapture->mergeForLead($validated, $request);
        if ($attribution !== []) {
            $lead->fill($attribution);
        }

        if (! config('marketing_attribution.enabled', true) || ! Schema::hasTable('marketing_attribution_sessions')) {
            return;
        }

        $landingOverride = isset($validated['landing_page']) ? (string) $validated['landing_page'] : null;
        $context = $this->landingContextResolver->resolve($request, $landingOverride);
        $session = $this->sessionPersister->persist($request, $context)
            ?? $this->sessionPersister->currentSession($request);

        $fkAttributes = $context->foreignKeyAttributes();
        if ($session !== null) {
            $fkAttributes = array_merge($fkAttributes, array_filter([
                'marketing_attribution_session_id' => $session->id,
                'page_id' => $session->page_id ?? ($fkAttributes['page_id'] ?? null),
                'service_id' => $session->service_id ?? ($fkAttributes['service_id'] ?? null),
                'pin_code_id' => $lead->pin_code_id ?? $session->pin_code_id ?? ($fkAttributes['pin_code_id'] ?? null),
                'service_location_page_id' => $session->service_location_page_id ?? ($fkAttributes['service_location_page_id'] ?? null),
            ], fn ($value) => $value !== null));
        }

        if ($fkAttributes !== []) {
            $lead->fill($fkAttributes);
        }
    }
}
