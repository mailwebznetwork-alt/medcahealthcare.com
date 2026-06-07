<?php

namespace App\Services\Launch;

use App\Models\Integration;
use App\Models\MarketingSetting;
use App\Services\Integrations\WhatsAppClickToChatService;
use App\Support\GrowthReadinessReport;

/**
 * Validates GTM, GA4, Search Console, WhatsApp, and conversion tracking readiness.
 */
class TrackingValidationService
{
    public function __construct(
        private readonly WhatsAppClickToChatService $whatsApp,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function audit(): array
    {
        $gtm = $this->resolveTag('google_tag_manager');
        $ga4 = $this->resolveTag('google_services');
        $meta = $this->resolveTag('meta_ads');
        $whatsAppReady = $this->whatsApp->activeNumbers() !== [];
        $growth = GrowthReadinessReport::build();

        return [
            'gtm_configured' => filled($gtm),
            'ga4_configured' => filled($ga4),
            'meta_configured' => filled($meta),
            'gtm_or_ga4_ready' => filled($gtm) || filled($ga4),
            'whatsapp_ready' => $whatsAppReady,
            'conversion_events' => config('marketing_automation.ga4_conversion_events', []),
            'tracking_components' => [
                'tracking-head.blade.php' => file_exists(resource_path('views/components/marketing/tracking-head.blade.php')),
                'tracking-events.blade.php' => file_exists(resource_path('views/components/marketing/tracking-events.blade.php')),
            ],
            'pincode_tracking' => 'EnsurePincodeDetected middleware + ChangePincodeEngine',
            'service_tracking' => 'MarketingClickTrackingService + medcaTrack() events',
            'growth_readiness' => $growth,
            'search_console' => [
                'verification_field' => 'seo_technical.google_site_verification',
                'configured' => filled(\App\Models\SeoTechnical::query()->value('google_site_verification')),
            ],
        ];
    }

    private function resolveTag(string $integrationName): ?string
    {
        $integration = Integration::query()->where('name', $integrationName)->where('is_enabled', true)->first();
        if ($integration !== null) {
            return $integration->getCredential('container_id')
                ?? $integration->getCredential('measurement_id')
                ?? $integration->getCredential('pixel_id');
        }

        if ($integrationName === 'google_services') {
            return MarketingSetting::current()->ga4_measurement_id;
        }

        return null;
    }
}
