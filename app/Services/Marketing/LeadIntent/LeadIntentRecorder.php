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

        return LeadIntentEvent::query()->create(array_merge([
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
        ], $this->attributionForeignKeysFromClick($event)));
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

        return LeadIntentEvent::query()->create(array_merge([
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
        ], $this->attributionForeignKeysFromLead($lead)));
    }

    /**
     * @return array<string, int|null>
     */
    private function attributionForeignKeysFromClick(MarketingClickEvent $event): array
    {
        return array_filter([
            'marketing_attribution_session_id' => $event->marketing_attribution_session_id,
            'page_id' => $event->page_id,
            'service_id' => $event->service_id,
            'pin_code_id' => $event->pin_code_id,
            'service_location_page_id' => $event->service_location_page_id,
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array<string, int|null>
     */
    private function attributionForeignKeysFromLead(Lead $lead): array
    {
        return array_filter([
            'marketing_attribution_session_id' => $lead->marketing_attribution_session_id,
            'page_id' => $lead->page_id,
            'service_id' => $lead->service_id,
            'pin_code_id' => $lead->pin_code_id,
            'service_location_page_id' => $lead->service_location_page_id,
        ], fn ($value) => $value !== null);
    }

    private function enabled(): bool
    {
        return config('lead_intent.enabled', true)
            && config('marketing_automation.enabled', true);
    }
}
