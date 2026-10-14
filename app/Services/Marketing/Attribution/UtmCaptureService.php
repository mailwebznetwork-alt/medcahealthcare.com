<?php

namespace App\Services\Marketing\Attribution;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UtmCaptureService
{
    public function __construct(
        private readonly AttributionSessionStore $store,
        private readonly DeviceContextResolver $deviceContext,
    ) {}

    public function captureFromRequest(Request $request): void
    {
        if (! config('marketing_automation.enabled', true) || ! config('marketing_automation.attribution.enabled', true)) {
            return;
        }

        $payload = $this->extractUtmPayload($request);
        if ($payload === []) {
            return;
        }

        $payload['captured_at'] = now()->toIso8601String();
        $payload['landing_page'] = $payload['landing_page'] ?? $request->path();
        $payload['referrer_url'] = $payload['referrer_url'] ?? $request->headers->get('referer');
        $payload = array_merge($payload, $this->deviceContext->resolve($request->userAgent()));
        $payload = array_merge($payload, $this->deviceContext->geoFromRequest($request));

        $this->store->persistFirstTouch($payload);
        $this->store->persistLastTouch($request, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function extractUtmPayload(Request $request): array
    {
        $fields = [
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
            'gclid', 'fbclid',
        ];

        $payload = [];
        foreach ($fields as $field) {
            $value = $request->query($field);
            if (is_string($value) && trim($value) !== '') {
                $payload[$field] = $this->sanitize($value, $field);
            }
        }

        $payload = $this->normalizeClickIdsToChannelUtms($payload);

        if ($payload === [] && ! $request->query->has('gclid') && ! $request->query->has('fbclid')) {
            $referrer = (string) $request->headers->get('referer', '');
            if ($referrer !== '' && ! str_contains($referrer, $request->getHost())) {
                $payload['utm_source'] = 'referral';
                $payload['utm_medium'] = 'referral';
            } elseif ($request->path() !== '/' && $request->query() === []) {
                $payload['utm_source'] = 'direct';
                $payload['utm_medium'] = 'direct';
            }
        }

        return $payload;
    }

    /**
     * When Google/Meta click IDs arrive without UTMs (auto-tagging only), map to
     * GA4 Default Channel Group–compatible source/medium values.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeClickIdsToChannelUtms(array $payload): array
    {
        $hasGclid = isset($payload['gclid']) && is_string($payload['gclid']) && $payload['gclid'] !== '';
        $hasFbclid = isset($payload['fbclid']) && is_string($payload['fbclid']) && $payload['fbclid'] !== '';

        if ($hasGclid) {
            $payload['utm_source'] ??= 'google';
            $payload['utm_medium'] ??= 'cpc';
        } elseif ($hasFbclid) {
            $payload['utm_source'] ??= 'facebook';
            $payload['utm_medium'] ??= 'paid';
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $requestData
     * @return array<string, mixed>
     */
    public function mergeForLead(array $requestData, Request $request): array
    {
        $first = $this->store->firstTouch();
        $last = $this->store->lastTouch($request);
        $device = $this->deviceContext->resolve($request->userAgent());
        $geo = $this->deviceContext->geoFromRequest($request);

        $utmSource = $requestData['utm_source'] ?? $last['utm_source'] ?? $first['utm_source'] ?? null;
        $utmMedium = $requestData['utm_medium'] ?? $last['utm_medium'] ?? $first['utm_medium'] ?? null;
        $utmCampaign = $requestData['utm_campaign'] ?? $requestData['campaign'] ?? $last['utm_campaign'] ?? $first['utm_campaign'] ?? null;

        return array_filter([
            'lead_source' => $utmSource,
            'lead_medium' => $utmMedium,
            'lead_campaign' => $utmCampaign,
            'lead_content' => $requestData['utm_content'] ?? $last['utm_content'] ?? $first['utm_content'] ?? null,
            'lead_term' => $requestData['utm_term'] ?? $last['utm_term'] ?? $first['utm_term'] ?? null,
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $utmCampaign,
            'utm_content' => $requestData['utm_content'] ?? $last['utm_content'] ?? $first['utm_content'] ?? null,
            'utm_term' => $requestData['utm_term'] ?? $last['utm_term'] ?? $first['utm_term'] ?? null,
            'gclid' => $requestData['gclid'] ?? $last['gclid'] ?? $first['gclid'] ?? null,
            'fbclid' => $requestData['fbclid'] ?? $last['fbclid'] ?? $first['fbclid'] ?? null,
            'landing_page' => $requestData['landing_page'] ?? $last['landing_page'] ?? $first['landing_page'] ?? $request->path(),
            'referrer_url' => $requestData['referrer_url'] ?? $last['referrer_url'] ?? $first['referrer_url'] ?? $request->headers->get('referer'),
            'first_touch_source' => $first['utm_source'] ?? $utmSource,
            'first_touch_medium' => $first['utm_medium'] ?? $utmMedium,
            'first_touch_campaign' => $first['utm_campaign'] ?? $utmCampaign,
            'first_touch_at' => isset($first['captured_at']) ? $first['captured_at'] : now(),
            'last_touch_source' => $last['utm_source'] ?? $utmSource,
            'last_touch_medium' => $last['utm_medium'] ?? $utmMedium,
            'last_touch_campaign' => $last['utm_campaign'] ?? $utmCampaign,
            'device_type' => $device['device_type'],
            'browser' => $device['browser'],
            'operating_system' => $device['operating_system'],
            'country' => $geo['country'],
            'region' => $geo['region'],
            'city' => $geo['city'],
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function sanitize(string $value, string $field): string
    {
        $max = str_contains($field, 'campaign') || str_contains($field, 'clid') ? 255 : 128;
        $clean = Str::of(strip_tags($value))->trim()->toString();

        return mb_substr($clean, 0, $max);
    }
}
