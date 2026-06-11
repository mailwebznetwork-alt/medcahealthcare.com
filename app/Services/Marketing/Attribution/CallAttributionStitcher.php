<?php

namespace App\Services\Marketing\Attribution;

use App\Models\Lead;
use App\Models\LeadIntentEvent;
use App\Models\MarketingAttributionSession;
use App\Models\MarketingClickEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Stitches phone clicks → leads and enriches FK attribution columns.
 */
class CallAttributionStitcher
{
    public function __construct(
        private readonly AttributionSessionPersister $sessionPersister,
    ) {}

    public function stitchLead(Lead $lead, Request $request): void
    {
        if (! config('marketing_attribution.enabled', true)) {
            return;
        }

        $session = $this->resolveSession($lead, $request);
        if ($session === null) {
            $this->stitchClicksToLead($lead);

            return;
        }

        $this->applySessionToLead($lead, $session);
        $lead->save();

        $this->markSessionConverted($session, $lead);
        $this->stitchClicksToLead($lead, $session);
        $this->backfillLeadIntents($lead);
    }

    private function resolveSession(Lead $lead, Request $request): ?MarketingAttributionSession
    {
        if (! Schema::hasTable('marketing_attribution_sessions')) {
            return null;
        }

        $sessionId = $lead->marketing_attribution_session_id ?? $this->sessionPersister->currentSessionId($request);
        if ($sessionId !== null) {
            $session = MarketingAttributionSession::query()->find($sessionId);
            if ($session !== null) {
                return $session;
            }
        }

        $fingerprint = $this->sessionPersister->sessionFingerprint($request);
        if ($fingerprint === '') {
            return null;
        }

        return MarketingAttributionSession::query()
            ->where('session_fingerprint', $fingerprint)
            ->orderByDesc('last_seen_at')
            ->first();
    }

    private function applySessionToLead(Lead $lead, MarketingAttributionSession $session): void
    {
        $attributes = array_filter([
            'marketing_attribution_session_id' => $session->id,
            'page_id' => $session->page_id,
            'service_id' => $session->service_id,
            'pin_code_id' => $lead->pin_code_id ?? $session->pin_code_id,
            'service_location_page_id' => $session->service_location_page_id,
        ], fn ($value) => $value !== null);

        if ($attributes !== []) {
            $lead->fill($attributes);
        }

        if (! filled($lead->landing_page) && filled($session->landing_page_path)) {
            $lead->landing_page = $session->landing_page_path;
        }
    }

    private function markSessionConverted(MarketingAttributionSession $session, Lead $lead): void
    {
        if ($session->converted_lead_id === null) {
            $session->converted_lead_id = $lead->id;
            $session->save();
        }
    }

    private function stitchClicksToLead(Lead $lead, ?MarketingAttributionSession $session = null): void
    {
        if (! Schema::hasTable('marketing_click_events')) {
            return;
        }

        $windowMinutes = config('marketing_attribution.click_stitch_window_minutes', 120);
        $eventTypes = config('marketing_attribution.phone_click_event_types', ['phone_click', 'whatsapp_click']);

        $query = MarketingClickEvent::query()
            ->whereIn('event_type', $eventTypes)
            ->whereNull('lead_id')
            ->where('occurred_at', '>=', now()->subMinutes($windowMinutes));

        if ($session !== null) {
            $query->where(function ($q) use ($session): void {
                $q->where('marketing_attribution_session_id', $session->id)
                    ->orWhere('session_fingerprint', $session->session_fingerprint);
            });
        } elseif (filled($lead->marketing_attribution_session_id)) {
            $query->where('marketing_attribution_session_id', $lead->marketing_attribution_session_id);
        } elseif (filled($lead->landing_page)) {
            $path = $this->normalizeLandingPath((string) $lead->landing_page);
            $query->where(function ($q) use ($path, $lead): void {
                $q->where('page_path', $path);
                if (filled($lead->service_id)) {
                    $q->orWhere('service_id', $lead->service_id);
                }
            });
        } else {
            return;
        }

        $fkAttributes = array_filter([
            'lead_id' => $lead->id,
            'marketing_attribution_session_id' => $lead->marketing_attribution_session_id,
            'page_id' => $lead->page_id,
            'service_id' => $lead->service_id,
            'pin_code_id' => $lead->pin_code_id,
            'service_location_page_id' => $lead->service_location_page_id,
        ], fn ($value) => $value !== null);

        $query->update($fkAttributes);
    }

    private function backfillLeadIntents(Lead $lead): void
    {
        if (! Schema::hasTable('lead_intent_events')) {
            return;
        }

        $attributes = array_filter([
            'lead_id' => $lead->id,
            'marketing_attribution_session_id' => $lead->marketing_attribution_session_id,
            'page_id' => $lead->page_id,
            'service_id' => $lead->service_id,
            'pin_code_id' => $lead->pin_code_id,
            'service_location_page_id' => $lead->service_location_page_id,
        ], fn ($value) => $value !== null);

        $fingerprint = $this->sessionFingerprintForLead($lead);

        LeadIntentEvent::query()
            ->where(function ($q) use ($lead, $fingerprint): void {
                $q->where('lead_id', $lead->id);
                if ($fingerprint !== null) {
                    $q->orWhere(function ($inner) use ($fingerprint): void {
                        $inner->whereNull('lead_id')
                            ->where('session_fingerprint', $fingerprint);
                    });
                }
            })
            ->update($attributes);
    }

    private function sessionFingerprintForLead(Lead $lead): ?string
    {
        if (! filled($lead->marketing_attribution_session_id)) {
            return null;
        }

        return MarketingAttributionSession::query()
            ->whereKey($lead->marketing_attribution_session_id)
            ->value('session_fingerprint');
    }

    private function normalizeLandingPath(string $landingPage): string
    {
        $path = $landingPage;
        if (str_contains($landingPage, '://')) {
            $parsed = parse_url($landingPage);
            $path = is_array($parsed) ? (string) ($parsed['path'] ?? '/') : '/';
        }

        $path = '/'.ltrim($path, '/');

        return rtrim($path, '/') ?: '/';
    }
}
