<?php

namespace App\Services;

use App\Enums\LeadSource;

class LeadSourceResolver
{
    public function resolve(?string $explicitSource, ?string $utmSource, ?string $gclid = null, ?string $fbclid = null): LeadSource
    {
        if ($explicitSource !== null && $explicitSource !== '') {
            $ex = trim($explicitSource);
            $try = LeadSource::tryFrom($ex)
                ?? LeadSource::tryFromLabelOrKey($ex);

            if ($try !== null) {
                return $try;
            }
        }

        if ($gclid !== null && trim($gclid) !== '') {
            return LeadSource::GoogleAds;
        }

        if ($fbclid !== null && trim($fbclid) !== '') {
            return LeadSource::MetaAds;
        }

        $utm = $utmSource !== null && $utmSource !== '' ? mb_strtolower(trim($utmSource)) : '';

        return match (true) {
            str_contains($utm, 'google') && (str_contains($utm, 'ad') || str_contains($utm, 'ads') || str_contains($utm, 'cpc')) => LeadSource::GoogleAds,
            str_contains($utm, 'gclid') => LeadSource::GoogleAds,
            str_contains($utm, 'facebook') || str_contains($utm, 'fb') || str_contains($utm, 'instagram') || str_contains($utm, 'meta') => LeadSource::MetaAds,
            str_contains($utm, 'whatsapp') || str_contains($utm, 'wa') => LeadSource::WhatsApp,
            str_contains($utm, 'call') => LeadSource::Call,
            str_contains($utm, 'gmb') || str_contains($utm, 'business') || str_contains($utm, 'gbp') => LeadSource::Gmb,
            str_contains($utm, 'linkedin') || str_contains($utm, 'lnkd') => LeadSource::LinkedIn,
            str_contains($utm, 'email') || str_contains($utm, 'newsletter') || str_contains($utm, 'mail') => LeadSource::Email,
            str_contains($utm, 'referral') || str_contains($utm, 'referrer') => LeadSource::Referral,
            $utm === 'direct' || str_contains($utm, '(direct)') => LeadSource::Direct,
            $utm === 'organic' || $utm === '' => LeadSource::Organic,
            default => LeadSource::Organic,
        };
    }
}
