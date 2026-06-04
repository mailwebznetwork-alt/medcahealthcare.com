<?php

namespace App\Services\Marketing\LeadIntent;

use App\Enums\LeadIntentChannel;
use App\Models\Lead;
use App\Models\LeadIntentEvent;
use App\Models\MarketingClickEvent;
use Illuminate\Support\Facades\Schema;

class LeadIntentRecorder
{
    public function __construct(
        private readonly LeadIntentMapper $mapper,
        private readonly LeadAttributionClassifier $classifier,
    ) {}

    public function recordFromMarketingClick(MarketingClickEvent $event): ?LeadIntentEvent
    {
        if (! $this->enabled() || ! Schema::hasTable('lead_intent_events')) {
            return null;
        }

        $intentType = $this->mapper->intentTypeFromMarketingEvent($event->event_type);
        if ($intentType === null) {
            return null;
        }

        if (LeadIntentEvent::query()->where('marketing_click_event_id', $event->id)->exists()) {
            return null;
        }

        $channel = $this->mapper->channelForIntentType($intentType);
        $bucket = $this->classifier->classify(
            $event->source,
            $event->medium,
            $event->campaign,
            $intentType,
        );

        return LeadIntentEvent::query()->create([
            'intent_type' => $intentType,
            'channel' => $channel,
            'attribution_bucket' => $bucket,
            'source' => $event->source,
            'medium' => $event->medium,
            'campaign' => $event->campaign,
            'landing_page' => $event->page_path,
            'service_page' => $this->mapper->servicePageFromPath($event->page_path),
            'lead_id' => $event->lead_id,
            'marketing_click_event_id' => $event->id,
            'meta' => $this->mapper->metaFromMarketingClick($event) ?: null,
            'session_fingerprint' => $event->session_fingerprint,
            'occurred_at' => $event->occurred_at ?? now(),
        ]);
    }

    public function recordFromLead(Lead $lead): ?LeadIntentEvent
    {
        if (! $this->enabled() || ! Schema::hasTable('lead_intent_events')) {
            return null;
        }

        if (LeadIntentEvent::query()->where('lead_id', $lead->id)->exists()) {
            return null;
        }

        $intentType = $this->mapper->intentTypeFromLead($lead);
        $channel = LeadIntentChannel::Forms;

        $bucket = $this->classifier->classify(
            $lead->utm_source ?? $lead->last_touch_source,
            $lead->utm_medium ?? $lead->last_touch_medium,
            $lead->utm_campaign ?? $lead->last_touch_campaign ?? $lead->campaign,
            $intentType,
            $lead,
        );

        return LeadIntentEvent::query()->create([
            'intent_type' => $intentType,
            'channel' => $channel,
            'attribution_bucket' => $bucket,
            'source' => $lead->utm_source ?? $lead->last_touch_source,
            'medium' => $lead->utm_medium ?? $lead->last_touch_medium,
            'campaign' => $lead->utm_campaign ?? $lead->campaign,
            'landing_page' => $lead->landing_page,
            'service_page' => $this->mapper->servicePageFromPath($lead->landing_page),
            'lead_id' => $lead->id,
            'meta' => [
                'lead_uuid' => $lead->uuid,
                'service' => $lead->service,
            ],
            'occurred_at' => $lead->created_at ?? now(),
        ]);
    }

    private function enabled(): bool
    {
        return config('lead_intent.enabled', true)
            && config('marketing_automation.enabled', true);
    }
}
