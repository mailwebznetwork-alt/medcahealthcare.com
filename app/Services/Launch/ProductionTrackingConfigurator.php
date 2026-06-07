<?php

namespace App\Services\Launch;

use App\Models\Integration;
use App\Models\MarketingSetting;
use App\Models\SeoTechnical;
use App\Models\BusinessProfile;

/**
 * Applies tracking configuration from config/env without changing tracking architecture.
 */
class ProductionTrackingConfigurator
{
    /**
     * @return array<string, mixed>
     */
    public function configure(): array
    {
        $results = [];

        $gtmId = config('medca_launch.gtm_container_id');
        if (filled($gtmId)) {
            Integration::query()->updateOrCreate(
                ['name' => 'google_tag_manager'],
                ['type' => 'analytics', 'is_enabled' => true, 'credentials' => ['container_id' => $gtmId, 'gtm_id' => $gtmId]]
            );
            $results['gtm'] = 'configured';
        }

        $ga4Id = config('medca_launch.ga4_measurement_id');
        if (filled($ga4Id)) {
            $marketing = MarketingSetting::current();
            $marketing->update(['ga4_measurement_id' => $ga4Id]);
            Integration::query()->updateOrCreate(
                ['name' => 'google_services'],
                ['type' => 'analytics', 'is_enabled' => true, 'credentials' => ['measurement_id' => $ga4Id]]
            );
            $results['ga4'] = 'configured';
        }

        $gscToken = config('medca_launch.google_site_verification');
        if (filled($gscToken)) {
            $profile = BusinessProfile::query()->latest('id')->first();
            SeoTechnical::query()->updateOrCreate(
                ['business_profile_id' => $profile?->id],
                [
                    'google_site_verification' => $gscToken,
                    'sitemap_enabled' => true,
                    'ai_discovery_enabled' => true,
                    'indexable' => true,
                ]
            );
            $results['search_console'] = 'configured';
        }

        $whatsapp = config('medca_launch.whatsapp_number') ?: $this->whatsappFromPublicUrl();
        if (filled($whatsapp)) {
            Integration::query()->updateOrCreate(
                ['name' => 'whatsapp'],
                [
                    'type' => 'messaging',
                    'is_enabled' => true,
                    'credentials' => [
                        'click_numbers' => [
                            ['number' => $whatsapp, 'label' => 'Primary', 'enabled' => true],
                        ],
                    ],
                ]
            );
            $results['whatsapp'] = 'configured';
        }

        $this->ensurePublicSeoFlags();

        return $results;
    }

    private function whatsappFromPublicUrl(): ?string
    {
        $url = (string) config('medca.whatsapp_url', '');
        if (preg_match('/wa\.me\/(\d+)/', $url, $matches) === 1) {
            return $matches[1];
        }

        $tel = (string) config('medca.phone_tel', '');

        return preg_replace('/\D+/', '', $tel) ?: null;
    }

    private function ensurePublicSeoFlags(): void
    {
        $profile = BusinessProfile::query()->latest('id')->first();
        SeoTechnical::query()->updateOrCreate(
            ['business_profile_id' => $profile?->id],
            [
                'sitemap_enabled' => true,
                'indexable' => true,
                'ai_discovery_enabled' => true,
            ]
        );
    }
}
