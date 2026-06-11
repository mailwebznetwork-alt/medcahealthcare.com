<?php

namespace App\Services\Marketing\Attribution;

use App\Models\MarketingAttributionSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AttributionSessionPersister
{
    public function __construct(
        private readonly AttributionSessionStore $attributionStore,
        private readonly UtmCaptureService $utmCapture,
    ) {}

    public function persist(Request $request, LandingContext $context): ?MarketingAttributionSession
    {
        if (! config('marketing_attribution.enabled', true) || ! Schema::hasTable('marketing_attribution_sessions')) {
            return null;
        }

        if (! $this->shouldPersistForRequest($request)) {
            return $this->currentSession($request);
        }

        if (! $request->hasSession()) {
            return null;
        }

        $fingerprint = $this->sessionFingerprint($request);
        if ($fingerprint === '') {
            return null;
        }

        $firstTouch = $this->attributionStore->firstTouch();
        $lastTouch = $this->attributionStore->lastTouch($request);
        $touch = $lastTouch !== [] ? $lastTouch : $firstTouch;

        $referrer = $touch['referrer_url']
            ?? $request->headers->get('referer');

        $attributes = array_merge($context->entityAttributes(), [
            'laravel_session_id' => $request->session()->getId(),
            'utm_source' => $touch['utm_source'] ?? null,
            'utm_medium' => $touch['utm_medium'] ?? null,
            'utm_campaign' => $touch['utm_campaign'] ?? null,
            'utm_term' => $touch['utm_term'] ?? null,
            'utm_content' => $touch['utm_content'] ?? null,
            'gclid' => $touch['gclid'] ?? null,
            'fbclid' => $touch['fbclid'] ?? null,
            'referrer' => is_string($referrer) ? mb_substr($referrer, 0, 500) : null,
            'first_touch_json' => $firstTouch !== [] ? $firstTouch : null,
            'last_touch_json' => $lastTouch !== [] ? $lastTouch : null,
            'last_seen_at' => now(),
        ]);

        $session = MarketingAttributionSession::query()->firstOrNew([
            'session_fingerprint' => $fingerprint,
        ]);

        if (! $session->exists) {
            $session->first_seen_at = now();
        }

        $session->fill($attributes);
        $session->save();

        $request->session()->put(
            config('marketing_attribution.session_id_key', 'marketing.attribution_session_id'),
            $session->id,
        );

        return $session;
    }

    public function currentSessionId(Request $request): ?int
    {
        if (! $request->hasSession()) {
            return null;
        }

        $id = $request->session()->get(
            config('marketing_attribution.session_id_key', 'marketing.attribution_session_id'),
        );

        return is_numeric($id) ? (int) $id : null;
    }

    public function sessionFingerprint(Request $request): string
    {
        if (! $request->hasSession()) {
            return '';
        }

        return $request->session()->getId();
    }

    public function currentSession(Request $request): ?MarketingAttributionSession
    {
        $sessionId = $this->currentSessionId($request);
        if ($sessionId === null) {
            return null;
        }

        return MarketingAttributionSession::query()->find($sessionId);
    }

    public function shouldPersistForRequest(Request $request): bool
    {
        return ! $request->is(
            'marketing/*',
            'api/*',
            'livewire/*',
            'up',
            'login',
            'logout',
            'register',
            'password/*',
            'email/*',
            'verify-email',
            't/mail/*',
        );
    }
}
