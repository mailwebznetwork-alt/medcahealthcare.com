<?php

namespace App\Services;

use App\Enums\LeadSource;

class LeadSourceResolver
{
    public function resolve(?string $explicitSource, ?string $utmSource): LeadSource
    {
        if ($explicitSource !== null && $explicitSource !== '') {
            $ex = trim($explicitSource);
            $try = LeadSource::tryFrom($ex)
                ?? LeadSource::tryFromLabelOrKey($ex);

            if ($try !== null) {
                return $try;
            }
        }

        $utm = $utmSource !== null && $utmSource !== '' ? mb_strtolower(trim($utmSource)) : '';

        return match (true) {
            str_contains($utm, 'google') && (str_contains($utm, 'ad') || str_contains($utm, 'ads') || str_contains($utm, 'cpc')) => LeadSource::GoogleAds,
            str_contains($utm, 'gclid') => LeadSource::GoogleAds,
            str_contains($utm, 'facebook') || str_contains($utm, 'fb') || str_contains($utm, 'instagram') || str_contains($utm, 'meta') => LeadSource::MetaAds,
            str_contains($utm, 'whatsapp') || str_contains($utm, 'wa') => LeadSource::WhatsApp,
            str_contains($utm, 'call') => LeadSource::Call,
            str_contains($utm, 'gmb') || str_contains($utm, 'business') => LeadSource::Gmb,
            $utm === 'organic' || $utm === 'direct' || $utm === '' => LeadSource::Organic,
            default => LeadSource::Organic,
        };
    }
}
