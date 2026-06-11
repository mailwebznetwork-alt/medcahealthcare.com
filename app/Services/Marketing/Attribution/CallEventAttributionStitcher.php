<?php

namespace App\Services\Marketing\Attribution;

use App\Models\CallEvent;
use App\Models\Lead;
use App\Models\MarketingAttributionSession;
use App\Models\MarketingClickEvent;
use Illuminate\Support\Facades\Schema;

/**
 * Stitches Exotel call events to phone clicks, sessions, and leads.
 */
class CallEventAttributionStitcher
{
    public function stitch(CallEvent $event): CallEvent
    {
        if (! config('marketing_attribution.enabled', true)) {
            return $event;
        }

        $click = $this->resolveClick($event);
        if ($click !== null) {
            $this->applyClickAttribution($event, $click);
        }

        $session = $this->resolveSession($event);
        if ($session !== null) {
            $this->applySessionAttribution($event, $session);
        }

        $lead = $this->resolveLead($event);
        if ($lead !== null) {
            $this->applyLeadAttribution($event, $lead);
        }

        $event->save();

        return $event;
    }

    private function resolveClick(CallEvent $event): ?MarketingClickEvent
    {
        if (! Schema::hasTable('marketing_click_events')) {
            return null;
        }

        if ($event->marketing_click_event_id !== null) {
            return MarketingClickEvent::query()->find($event->marketing_click_event_id);
        }

        $sessionId = $this->parseSessionIdFromCustomField($event->custom_field);
        $windowMinutes = config('exotel.stitch_window_minutes', 120);
        $eventTypes = config('marketing_attribution.phone_click_event_types', ['phone_click']);

        $query = MarketingClickEvent::query()
            ->whereIn('event_type', $eventTypes)
            ->where('occurred_at', '>=', now()->subMinutes($windowMinutes))
            ->orderByDesc('occurred_at');

        if ($sessionId !== null) {
            $click = (clone $query)->where('marketing_attribution_session_id', $sessionId)->first();
            if ($click !== null) {
                return $click;
            }
        }

        return $query->first();
    }

    private function resolveSession(CallEvent $event): ?MarketingAttributionSession
    {
        if (! Schema::hasTable('marketing_attribution_sessions')) {
            return null;
        }

        if ($event->marketing_attribution_session_id !== null) {
            return MarketingAttributionSession::query()->find($event->marketing_attribution_session_id);
        }

        $sessionId = $this->parseSessionIdFromCustomField($event->custom_field);
        if ($sessionId !== null) {
            return MarketingAttributionSession::query()->find($sessionId);
        }

        return null;
    }

    private function resolveLead(CallEvent $event): ?Lead
    {
        if ($event->lead_id !== null) {
            return Lead::query()->find($event->lead_id);
        }

        if ($event->caller_normalized === null || $event->caller_normalized === '') {
            return null;
        }

        return Lead::query()
            ->where('phone_normalized', $event->caller_normalized)
            ->orderByDesc('created_at')
            ->first();
    }

    private function applyClickAttribution(CallEvent $event, MarketingClickEvent $click): void
    {
        $event->fill(array_filter([
            'marketing_click_event_id' => $click->id,
            'marketing_attribution_session_id' => $click->marketing_attribution_session_id,
            'page_id' => $click->page_id,
            'service_id' => $click->service_id,
            'pin_code_id' => $click->pin_code_id,
            'service_location_page_id' => $click->service_location_page_id,
            'lead_id' => $click->lead_id ?? $event->lead_id,
        ], fn ($value) => $value !== null));
    }

    private function applySessionAttribution(CallEvent $event, MarketingAttributionSession $session): void
    {
        $event->fill(array_filter([
            'marketing_attribution_session_id' => $session->id,
            'page_id' => $event->page_id ?? $session->page_id,
            'service_id' => $event->service_id ?? $session->service_id,
            'pin_code_id' => $event->pin_code_id ?? $session->pin_code_id,
            'service_location_page_id' => $event->service_location_page_id ?? $session->service_location_page_id,
            'lead_id' => $event->lead_id ?? $session->converted_lead_id,
        ], fn ($value) => $value !== null));
    }

    private function applyLeadAttribution(CallEvent $event, Lead $lead): void
    {
        $event->fill(array_filter([
            'lead_id' => $lead->id,
            'marketing_attribution_session_id' => $event->marketing_attribution_session_id ?? $lead->marketing_attribution_session_id,
            'page_id' => $event->page_id ?? $lead->page_id,
            'service_id' => $event->service_id ?? $lead->service_id,
            'pin_code_id' => $event->pin_code_id ?? $lead->pin_code_id,
            'service_location_page_id' => $event->service_location_page_id ?? $lead->service_location_page_id,
        ], fn ($value) => $value !== null));
    }

    private function parseSessionIdFromCustomField(?string $customField): ?int
    {
        if ($customField === null || trim($customField) === '') {
            return null;
        }

        if (preg_match('/(?:session[_:-]?)?(\d+)/i', $customField, $matches) === 1) {
            return (int) $matches[1];
        }

        return is_numeric($customField) ? (int) $customField : null;
    }
}
