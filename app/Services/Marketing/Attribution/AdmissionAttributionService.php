<?php

namespace App\Services\Marketing\Attribution;

use App\Models\Admission;
use App\Models\Lead;

class AdmissionAttributionService
{
    public function applyFromLead(Admission $admission, ?Lead $lead): void
    {
        if ($lead === null) {
            return;
        }

        $admission->fill(array_filter([
            'lead_id' => $lead->id,
            'service_id' => $admission->service_id ?? $lead->service_id,
            'pin_code_id' => $admission->pin_code_id ?? $lead->pin_code_id,
            'service_location_page_id' => $admission->service_location_page_id ?? $lead->service_location_page_id,
            'marketing_attribution_session_id' => $admission->marketing_attribution_session_id ?? $lead->marketing_attribution_session_id,
        ], fn ($value) => $value !== null));
    }
}
