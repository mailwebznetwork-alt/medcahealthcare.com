<?php

namespace App\Services\Marketing\LeadIntent;

use App\Enums\LeadAttributionBucket;
use App\Enums\LeadSource;
use App\Models\Lead;

class LeadAttributionClassifier
{
    public function classify(
        ?string $source,
        ?string $medium,
        ?string $campaign,
        string $intentType,
        ?Lead $lead = null,
    ): LeadAttributionBucket {
        if ($lead !== null) {
            $fromLead = $this->fromLeadSource($lead);
            if ($fromLead !== null) {
                return $fromLead;
            }
        }

        if ($this->isGbpIntent($intentType)) {
            return LeadAttributionBucket::Gbp;
        }

        $s = strtolower(trim((string) $source));
        $m = strtolower(trim((string) $medium));

        if ($this->matchesGoogleAds($s, $m, $campaign)) {
            return LeadAttributionBucket::GoogleAds;
        }

        if ($this->matchesMetaAds($s, $m)) {
            return LeadAttributionBucket::MetaAds;
        }

        if ($m === 'referral' || $s === 'referral' || str_contains($s, 'referral')) {
            return LeadAttributionBucket::Referral;
        }

        if ($m === 'organic' || $s === 'organic' || $s === '(organic)' || str_contains($s, 'organic')) {
            return LeadAttributionBucket::Organic;
        }

        if ($s === '(direct)' || $s === 'direct' || ($s === '' && $m === '')) {
            return LeadAttributionBucket::Direct;
        }

        if ($s !== '' || $m !== '') {
            return LeadAttributionBucket::Organic;
        }

        return LeadAttributionBucket::Unknown;
    }

    private function fromLeadSource(Lead $lead): ?LeadAttributionBucket
    {
        $source = $lead->source instanceof LeadSource ? $lead->source : LeadSource::tryFrom((string) $lead->source);
        if (! $source instanceof LeadSource) {
            return null;
        }

        return match ($source) {
            LeadSource::GoogleAds => LeadAttributionBucket::GoogleAds,
            LeadSource::MetaAds => LeadAttributionBucket::MetaAds,
            LeadSource::Gmb => LeadAttributionBucket::Gbp,
            LeadSource::Organic => LeadAttributionBucket::Organic,
            LeadSource::Direct => LeadAttributionBucket::Direct,
            LeadSource::Referral => LeadAttributionBucket::Referral,
            default => null,
        };
    }

    private function isGbpIntent(string $intentType): bool
    {
        return str_starts_with($intentType, 'gbp_');
    }

    private function matchesGoogleAds(string $source, string $medium, ?string $campaign): bool
    {
        $googleSources = ['google', 'googleads', 'google_ads', 'adwords', 'gclid'];
        foreach ($googleSources as $needle) {
            if ($source === $needle || str_contains($source, $needle)) {
                if (in_array($medium, ['cpc', 'ppc', 'paid', 'paidsearch', 'cpm'], true)) {
                    return true;
                }
            }
        }

        if (str_contains(strtolower((string) $campaign), 'gads') || str_contains(strtolower((string) $campaign), 'google')) {
            return true;
        }

        return false;
    }

    private function matchesMetaAds(string $source, string $medium): bool
    {
        $metaSources = ['facebook', 'fb', 'meta', 'instagram', 'ig'];
        foreach ($metaSources as $needle) {
            if ($source === $needle || str_contains($source, $needle)) {
                if (in_array($medium, ['cpc', 'ppc', 'paid', 'paid_social', 'social'], true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
