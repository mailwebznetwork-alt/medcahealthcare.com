<?php

namespace App\Services\Marketing\LeadIntent;

use App\Enums\LeadIntentChannel;
use App\Enums\LeadSource;
use App\Models\Lead;
use App\Models\MarketingClickEvent;

class LeadIntentMapper
{
    public function intentTypeFromMarketingEvent(string $eventType): ?string
    {
        $map = config('lead_intent.marketing_event_map', []);

        return is_string($map[$eventType] ?? null) ? $map[$eventType] : null;
    }

    public function intentTypeFromLead(Lead $lead): string
    {
        $source = $lead->source instanceof LeadSource ? $lead->source->value : (string) $lead->source;
        $map = config('lead_intent.lead_source_intent_map', []);

        return is_string($map[$source] ?? null) ? $map[$source] : 'form_submit';
    }

    public function channelForIntentType(string $intentType): LeadIntentChannel
    {
        if (str_contains($intentType, 'whatsapp')) {
            return LeadIntentChannel::WhatsApp;
        }

        if (str_contains($intentType, 'call') || $intentType === 'phone_click') {
            return LeadIntentChannel::Calls;
        }

        if (str_contains($intentType, 'form') || str_contains($intentType, 'direction')) {
            return LeadIntentChannel::Forms;
        }

        if (str_contains($intentType, 'gbp_website')) {
            return LeadIntentChannel::Forms;
        }

        return LeadIntentChannel::Forms;
    }

    public function servicePageFromPath(?string $pagePath): ?string
    {
        if ($pagePath === null || $pagePath === '') {
            return null;
        }

        $path = '/'.ltrim($pagePath, '/');
        if (preg_match('#^/(services|service|packages|care-at-home|consulting)[/\w\-]*#i', $path)) {
            return $path;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function metaFromMarketingClick(MarketingClickEvent $event): array
    {
        $meta = is_array($event->meta) ? $event->meta : [];

        if ($event->element_label) {
            $meta['button_name'] = $meta['button_name'] ?? $event->element_label;
        }

        if ($event->destination_url) {
            $meta['destination_url'] = $event->destination_url;
            if (str_starts_with($event->destination_url, 'tel:')) {
                $meta['phone_number'] = $meta['phone_number'] ?? substr($event->destination_url, 4);
            }
        }

        return $meta;
    }
}
