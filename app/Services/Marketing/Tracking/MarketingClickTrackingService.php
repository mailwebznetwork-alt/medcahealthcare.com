<?php

namespace App\Services\Marketing\Tracking;

use App\Models\MarketingAttributionSession;
use App\Models\MarketingClickEvent;
use App\Services\Marketing\Attribution\AttributionSessionPersister;
use App\Services\Marketing\Attribution\AttributionSessionStore;
use App\Services\Marketing\Attribution\DeviceContextResolver;
use App\Services\Marketing\Attribution\LandingPageContextResolver;
use App\Services\Marketing\LeadIntent\LeadIntentRecorder;
use Illuminate\Http\Request;

class MarketingClickTrackingService
{
    public function __construct(
        private readonly MarketingTrackingValidator $validator,
        private readonly DeviceContextResolver $deviceContext,
        private readonly AttributionSessionStore $attributionStore,
        private readonly AttributionSessionPersister $sessionPersister,
        private readonly LandingPageContextResolver $landingContextResolver,
        private readonly LeadIntentRecorder $leadIntentRecorder,
    ) {}

    /**
     * @return array{recorded: bool, id: ?int}
     */
    public function record(Request $request): array
    {
        if (! config('marketing_automation.enabled', true) || ! config('marketing_automation.click_tracking.enabled', true)) {
            return ['recorded' => false, 'id' => null];
        }

        $data = $this->validator->validate($request);
        $fingerprint = (string) ($data['session_fingerprint'] ?? '');
        if ($fingerprint === '' && $request->hasSession()) {
            $fingerprint = $request->session()->getId();
        }

        if ($this->validator->isDuplicate($fingerprint, $data['event_type'])) {
            return ['recorded' => false, 'id' => null];
        }

        $lastTouch = $this->attributionStore->lastTouch($request);
        $device = $this->deviceContext->resolve($request->userAgent());

        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        if (! empty($data['phone_number'])) {
            $meta['phone_number'] = $data['phone_number'];
        }
        if (! empty($data['button_name'])) {
            $meta['button_name'] = $data['button_name'];
        }

        $pagePath = $data['page_path'] ?? '/'.ltrim($request->path(), '/');
        $attributionFk = $this->resolveAttributionForeignKeys($request, $pagePath);

        $event = MarketingClickEvent::query()->create(array_merge([
            'event_type' => $data['event_type'],
            'page_path' => $pagePath,
            'page_title' => $data['page_title'] ?? null,
            'campaign' => $data['campaign'] ?? ($lastTouch['utm_campaign'] ?? null),
            'source' => $data['source'] ?? ($lastTouch['utm_source'] ?? null),
            'medium' => $data['medium'] ?? ($lastTouch['utm_medium'] ?? null),
            'element_label' => $data['element_label'] ?? $data['button_name'] ?? null,
            'destination_url' => $data['destination_url'] ?? null,
            'device_type' => $device['device_type'],
            'browser' => $device['browser'],
            'operating_system' => $device['operating_system'],
            'session_fingerprint' => $fingerprint !== '' ? $fingerprint : null,
            'meta' => $meta !== [] ? $meta : null,
            'occurred_at' => now(),
        ], $attributionFk));

        $this->leadIntentRecorder->recordFromMarketingClick($event);

        return ['recorded' => true, 'id' => $event->id];
    }

    /**
     * @return array<string, int|null>
     */
    private function resolveAttributionForeignKeys(Request $request, string $pagePath): array
    {
        if (! config('marketing_attribution.enabled', true)) {
            return [];
        }

        $sessionId = $this->sessionPersister->currentSessionId($request);
        if ($sessionId !== null) {
            $session = MarketingAttributionSession::query()->find($sessionId);
            if ($session !== null) {
                return array_filter([
                    'marketing_attribution_session_id' => $session->id,
                    'page_id' => $session->page_id,
                    'service_id' => $session->service_id,
                    'pin_code_id' => $session->pin_code_id,
                    'service_location_page_id' => $session->service_location_page_id,
                ], fn ($value) => $value !== null);
            }
        }

        $context = $this->landingContextResolver->resolve($request, $pagePath);
        $this->sessionPersister->persist($request, $context);

        return array_filter(
            array_merge(
                ['marketing_attribution_session_id' => $this->sessionPersister->currentSessionId($request)],
                $context->foreignKeyAttributes(),
            ),
            fn ($value) => $value !== null,
        );
    }
}
