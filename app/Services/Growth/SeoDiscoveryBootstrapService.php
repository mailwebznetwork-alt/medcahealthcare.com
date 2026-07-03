<?php

namespace App\Services\Growth;

use App\Models\BusinessProfile;
use App\Models\SeoAiSignal;
use App\Models\SeoTechnical;
use App\Support\SeoTechnicalResolver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Phase A — one-click SEO discovery bootstrap (GSC, IndexNow, AI crawl, profile links).
 */
final class SeoDiscoveryBootstrapService
{
    public function __construct(
        private readonly SearchEngineDiscoveryService $discovery,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function run(bool $pingSearchEngines = true): array
    {
        $results = [
            'business_profile_linked' => false,
            'indexnow_key' => null,
            'ai_crawl_enabled' => false,
            'ai_discovery_enabled' => false,
            'gsc_sitemap_submitted' => false,
            'indexnow_notified' => false,
            'messages' => [],
        ];

        if (! Schema::hasTable('seo_technical') || ! Schema::hasTable('business_profiles')) {
            $results['messages'][] = __('SEO technical tables are not available.');

            return $results;
        }

        $profile = BusinessProfile::query()->where('website', config('app.url'))->first()
            ?? BusinessProfile::query()->latest('id')->first();

        if (! $profile instanceof BusinessProfile) {
            $profile = BusinessProfile::query()->create([
                'name' => config('medca.brand_name', 'Medca Healthcare'),
                'email' => config('mail.from.address'),
                'website' => config('app.url'),
            ]);
            $results['messages'][] = __('Created business profile for this site URL.');
        }

        $technical = SeoTechnicalResolver::forCurrentSite(repairOrphan: true)
            ?? SeoTechnical::query()->create([
                'business_profile_id' => $profile->id,
                'robots_txt' => null,
                'sitemap_enabled' => true,
                'canonical_url' => config('app.url'),
                'indexable' => true,
                'ai_discovery_enabled' => true,
            ]);

        if ($technical->business_profile_id === null) {
            $technical->forceFill(['business_profile_id' => $profile->id])->saveQuietly();
        }

        $results['business_profile_linked'] = (int) $technical->business_profile_id === (int) $profile->id;

        if (! filled($technical->indexnow_key)) {
            $technical->forceFill(['indexnow_key' => Str::lower(Str::random(32))])->saveQuietly();
            $results['messages'][] = __('Generated IndexNow key.');
        }

        if (! $technical->ai_discovery_enabled) {
            $technical->forceFill(['ai_discovery_enabled' => true])->saveQuietly();
            $results['messages'][] = __('Enabled public /ai-discovery.');
        }

        $results['indexnow_key'] = $technical->indexnow_key;
        $results['ai_discovery_enabled'] = (bool) $technical->ai_discovery_enabled;

        if (Schema::hasTable('seo_ai_signals')) {
            $existing = SeoAiSignal::query()->where('business_profile_id', $profile->id)->first();
            $signal = SeoAiSignal::query()->updateOrCreate(
                ['business_profile_id' => $profile->id],
                [
                    'ai_crawl_enabled' => true,
                    'llm_visibility_score' => (int) ($existing?->llm_visibility_score ?? 100),
                    'entity_consistency_score' => (int) ($existing?->entity_consistency_score ?? 100),
                ]
            );
            $results['ai_crawl_enabled'] = (bool) $signal->ai_crawl_enabled;
        }

        if ($pingSearchEngines) {
            $ping = $this->discovery->pingAfterSitemapUpdate();
            $results['gsc_sitemap_submitted'] = (bool) ($ping['gsc_submitted'] ?? false);
            $results['indexnow_notified'] = (bool) ($ping['indexnow_notified'] ?? false);
            foreach ($ping['messages'] ?? [] as $message) {
                $results['messages'][] = $message;
            }
        }

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $technical = SeoTechnicalResolver::forCurrentSite(repairOrphan: false);
        $signal = Schema::hasTable('seo_ai_signals')
            ? SeoAiSignal::query()->latest('id')->first()
            : null;
        $gsc = app(GoogleSearchConsoleService::class)->testConnection();

        return [
            'app_url' => config('app.url'),
            'indexnow_key' => $technical?->indexnow_key ?: config('growth.indexnow.key'),
            'indexnow_key_url' => filled($technical?->indexnow_key)
                ? url('/'.$technical->indexnow_key.'.txt')
                : null,
            'ai_discovery_enabled' => (bool) ($technical?->ai_discovery_enabled ?? false),
            'ai_crawl_enabled' => (bool) ($signal?->ai_crawl_enabled ?? false),
            'bing_verification_set' => filled($technical?->bing_site_verification),
            'google_verification_set' => filled($technical?->google_site_verification),
            'gsc_configured' => (bool) ($gsc['configured'] ?? false),
            'gsc_sites' => $gsc['sites'] ?? [],
            'sitemap_url' => url('/sitemap.xml'),
        ];
    }
}
