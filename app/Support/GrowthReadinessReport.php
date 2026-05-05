<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\BusinessProfile;
use App\Models\GeoLocation;
use App\Models\Integration;
use App\Models\MarketingSetting;
use App\Models\SeoEntity;
use App\Models\SeoTechnical;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Rule-based SEO & marketing tag readiness for Growth Center (Medca MarketingSeoHealthReport equivalent).
 * Does not call Google/Meta APIs; scores reflect DB + config + enabled integrations.
 *
 * Strings are English for stable caching; translate in Blade with __().
 */
final class GrowthReadinessReport
{
    public const string CACHE_KEY = 'markonminds:growth_readiness_report:v1';

    public const int CACHE_TTL_SECONDS = 90;

    /**
     * @return array{
     *     sections: array<string, array{label: string, score: int, items: list<array{label: string, status: string, detail: string}>}>,
     *     overall_score: int,
     *     suggestions: list<array{priority: string, text: string, href: string|null}>,
     *     health_row: list<array{id: string, label: string, score: int, blurb: string, href: string}>,
     *     score_seo_core: int,
     *     score_entity: int,
     *     score_signals: int
     * }
     */
    public static function build(): array
    {
        $appUrl = (string) config('app.url', '');

        $profile = null;
        $seoEntity = null;
        $seoTechnical = null;

        if (Schema::hasTable('business_profiles')) {
            $profile = BusinessProfile::query()->where('website', $appUrl)->first()
                ?? BusinessProfile::query()->latest('id')->first();
        }

        if ($profile !== null && Schema::hasTable('seo_entities')) {
            $seoEntity = SeoEntity::query()->where('business_profile_id', $profile->id)->first();
        }
        if ($seoEntity === null && Schema::hasTable('seo_entities')) {
            $seoEntity = SeoEntity::query()->latest('id')->first();
        }

        if ($profile !== null && Schema::hasTable('seo_technical')) {
            $seoTechnical = SeoTechnical::query()->where('business_profile_id', $profile->id)->first();
        }
        if ($seoTechnical === null && Schema::hasTable('seo_technical')) {
            $seoTechnical = SeoTechnical::query()->latest('id')->first();
        }

        $geo = Schema::hasTable('geo_locations') ? GeoLocation::query()->latest('id')->first() : null;

        $marketing = null;
        try {
            $marketing = MarketingSetting::current();
        } catch (\Throwable) {
            $marketing = null;
        }

        $seoCoreItems = self::seoCoreItems($seoEntity, $profile, $appUrl);
        $discoveryItems = self::discoveryItems($seoTechnical, $geo);
        $seoItems = array_merge($seoCoreItems, $discoveryItems);

        $trackingItems = self::trackingItems($marketing);

        $seoCoreScore = self::sectionScore($seoCoreItems);
        $entityScore = self::sectionScore($discoveryItems);
        $seoScore = self::sectionScore($seoItems);
        $trackingScore = self::sectionScore($trackingItems);

        $aioIndex = (int) round(($seoCoreScore + $entityScore + $trackingScore) / 3);

        $sections = [
            'seo' => [
                'label' => 'SEO & structured data',
                'score' => $seoScore,
                'items' => array_map(self::stripWeight(...), $seoItems),
            ],
            'tracking' => [
                'label' => 'Analytics, GTM & pixels',
                'score' => $trackingScore,
                'items' => array_map(self::stripWeight(...), $trackingItems),
            ],
        ];

        $overall = (int) round(($seoScore + $trackingScore) / 2);

        return [
            'sections' => $sections,
            'overall_score' => $overall,
            'suggestions' => self::suggestionsFromItems($seoItems, $trackingItems),
            'health_row' => [
                [
                    'id' => 'aio',
                    'label' => 'AIO index',
                    'score' => $aioIndex,
                    'blurb' => 'Composite: schema, discovery, and signal readiness.',
                    'href' => self::hrefEntity(),
                ],
                [
                    'id' => 'seo',
                    'label' => 'SEO & schema',
                    'score' => $seoCoreScore,
                    'blurb' => 'On-page and JSON-LD entity fields.',
                    'href' => self::hrefEntity(),
                ],
                [
                    'id' => 'entity',
                    'label' => 'Discovery & GEO',
                    'score' => $entityScore,
                    'blurb' => 'Verification tokens and hub coordinates.',
                    'href' => self::hrefTechnical(),
                ],
                [
                    'id' => 'signals',
                    'label' => 'Analytics & tags',
                    'score' => $trackingScore,
                    'blurb' => 'GA4, GTM, Meta, CAPI, Clarity.',
                    'href' => self::hrefMarketing(),
                ],
            ],
            'score_seo_core' => $seoCoreScore,
            'score_entity' => $entityScore,
            'score_signals' => $trackingScore,
        ];
    }

    public static function cached(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            static fn (): array => self::build()
        );
    }

    public static function forget(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Throwable) {
        }
    }

    private static function integrationValue(string $integrationName, string $credentialKey): string
    {
        if (! Schema::hasTable('integrations')) {
            return '';
        }

        $row = Integration::query()->where('name', $integrationName)->first();
        if ($row === null || ! $row->is_enabled) {
            return '';
        }

        $v = $row->getCredential($credentialKey);
        if (! is_string($v)) {
            return '';
        }

        return trim($v);
    }

    /**
     * @param  list<array{label: string, status: string, detail: string, weight: float}>  $items
     */
    private static function sectionScore(array $items): int
    {
        $total = 0.0;
        $earned = 0.0;
        foreach ($items as $item) {
            $w = (float) $item['weight'];
            $total += $w;
            $earned += match ($item['status']) {
                'pass' => $w,
                'warn' => $w * 0.5,
                default => 0.0,
            };
        }

        if ($total <= 0.0) {
            return 0;
        }

        return (int) round(100 * $earned / $total);
    }

    /**
     * @param  array{label: string, status: string, detail: string, weight: float}  $item
     * @return array{label: string, status: string, detail: string}
     */
    private static function stripWeight(array $item): array
    {
        return [
            'label' => $item['label'],
            'status' => $item['status'],
            'detail' => $item['detail'],
        ];
    }

    /**
     * @return list<array{label: string, status: string, detail: string, weight: float}>
     */
    private static function seoCoreItems(?SeoEntity $seo, ?BusinessProfile $profile, string $appUrl): array
    {
        $desc = trim((string) ($seo?->meta_description ?? ''));
        $descLen = strlen($desc);
        if ($descLen >= 120) {
            $descStatus = 'pass';
            $descDetail = sprintf('Meta description is %d characters (good length for snippets).', $descLen);
        } elseif ($descLen >= 50) {
            $descStatus = 'warn';
            $descDetail = sprintf('Description is %d characters — aim for ~120–160 for richer SERP snippets.', $descLen);
        } else {
            $descStatus = 'fail';
            $descDetail = $descLen === 0 ? 'No site description set on the SEO entity.' : sprintf('Description is only %d characters.', $descLen);
        }

        $phone = trim((string) ($profile?->phone_e164 ?? ''));
        $phoneOk = $phone !== '' && preg_match('/^\+[1-9]\d{6,14}$/', $phone) === 1;
        $phoneStatus = $phoneOk ? 'pass' : ($phone !== '' ? 'warn' : 'fail');
        $phoneDetail = $phoneOk
            ? 'Telephone is in E.164 format.'
            : ($phone === '' ? 'Add a telephone in E.164 (e.g. +91…) on the business profile.' : 'Telephone should be E.164 (+country code, digits only).');

        $logo = trim((string) ($seo?->logo ?? ''));
        $og = trim((string) ($seo?->og_image_url ?? ''));
        $hasImage = ($logo !== '' && filter_var($logo, FILTER_VALIDATE_URL))
            || ($og !== '' && filter_var($og, FILTER_VALIDATE_URL));
        $imageStatus = $hasImage ? 'pass' : 'fail';
        $imageDetail = $hasImage
            ? 'Logo or Open Graph image URL is set.'
            : 'Set a valid logo URL or OG image for sharing and rich results.';

        $street = trim((string) ($profile?->street_address ?? ''));
        $streetStatus = $street !== '' ? 'pass' : 'warn';
        $streetDetail = $street !== ''
            ? 'Street address supports LocalBusiness-style signals.'
            : 'Add a street address for stronger local SEO signals.';

        $sameAs = $seo?->same_as ?? [];
        $sameAsCount = is_array($sameAs) ? count($sameAs) : 0;
        $sameStatus = $sameAsCount >= 1 ? 'pass' : 'warn';
        $sameDetail = $sameAsCount >= 1
            ? sprintf('Entity links (sameAs): %d profile URL(s).', $sameAsCount)
            : 'Add at least one official profile URL (Google Business, social) in sameAs.';

        $postal = trim((string) ($profile?->postal_code ?? ''));
        $postalStatus = $postal !== '' ? 'pass' : 'warn';
        $postalDetail = $postal !== '' ? 'Postal code is set.' : 'Postal code helps local and address completeness.';

        $local = app()->environment(['local', 'testing']);
        if ($local) {
            $httpsStatus = 'pass';
            $httpsDetail = 'HTTPS on APP_URL is not scored in local/testing.';
        } elseif (str_starts_with(strtolower($appUrl), 'https://')) {
            $httpsStatus = 'pass';
            $httpsDetail = 'APP_URL uses HTTPS.';
        } else {
            $httpsStatus = 'fail';
            $httpsDetail = 'Use HTTPS for APP_URL in production for trust, SEO, and secure cookies.';
        }

        $org = trim((string) ($seo?->organization_name ?? ''));
        $orgStatus = $org !== '' ? 'pass' : 'fail';
        $orgDetail = $org !== '' ? 'Organization name is set.' : 'Set organization name for titles and JSON-LD.';

        return [
            ['label' => 'Site description', 'status' => $descStatus, 'detail' => $descDetail, 'weight' => 2.0],
            ['label' => 'Organization name', 'status' => $orgStatus, 'detail' => $orgDetail, 'weight' => 1.0],
            ['label' => 'Telephone (E.164)', 'status' => $phoneStatus, 'detail' => $phoneDetail, 'weight' => 2.0],
            ['label' => 'Logo or OG image', 'status' => $imageStatus, 'detail' => $imageDetail, 'weight' => 2.0],
            ['label' => 'Street address', 'status' => $streetStatus, 'detail' => $streetDetail, 'weight' => 1.5],
            ['label' => 'Entity links (sameAs)', 'status' => $sameStatus, 'detail' => $sameDetail, 'weight' => 1.5],
            ['label' => 'Postal code', 'status' => $postalStatus, 'detail' => $postalDetail, 'weight' => 1.0],
            ['label' => 'HTTPS (APP_URL)', 'status' => $httpsStatus, 'detail' => $httpsDetail, 'weight' => 2.0],
        ];
    }

    /**
     * @return list<array{label: string, status: string, detail: string, weight: float}>
     */
    private static function discoveryItems(?SeoTechnical $technical, ?GeoLocation $geo): array
    {
        $googleToken = trim((string) ($technical?->google_site_verification ?? ''));
        $googleStatus = $googleToken !== '' ? 'pass' : 'warn';
        $googleDetail = $googleToken !== ''
            ? 'Google Search Console verification token is saved.'
            : 'Add Google site verification under SEO technical settings.';

        $bingKey = self::integrationValue('bing_webmaster', 'api_key');
        $bingStatus = $bingKey !== '' ? 'pass' : 'warn';
        $bingDetail = $bingKey !== ''
            ? 'Bing Webmaster integration has an API key saved.'
            : 'Optional: add Bing Webmaster under Integrations for Microsoft Search coverage.';

        $lat = $geo?->latitude;
        $lng = $geo?->longitude;
        $geoOk = $lat !== null && $lng !== null && ((float) $lat !== 0.0 || (float) $lng !== 0.0);
        $geoStatus = $geoOk ? 'pass' : 'warn';
        $geoDetail = $geoOk
            ? 'Hub latitude and longitude are set for GEO context.'
            : 'Set hub coordinates under Growth GEO & location.';

        $aiOn = (bool) ($technical?->ai_discovery_enabled ?? false);
        $aiStatus = $aiOn ? 'pass' : 'warn';
        $aiDetail = $aiOn
            ? 'AI discovery hints are enabled where configured.'
            : 'Optional: enable AI discovery / llm.txt alignment for third-party crawlers (review policy first).';

        return [
            ['label' => 'Google Search Console verification', 'status' => $googleStatus, 'detail' => $googleDetail, 'weight' => 1.5],
            ['label' => 'Bing Webmaster integration', 'status' => $bingStatus, 'detail' => $bingDetail, 'weight' => 1.0],
            ['label' => 'Hub coordinates (GEO)', 'status' => $geoStatus, 'detail' => $geoDetail, 'weight' => 1.5],
            ['label' => 'AI discovery flags', 'status' => $aiStatus, 'detail' => $aiDetail, 'weight' => 1.0],
        ];
    }

    /**
     * @return list<array{label: string, status: string, detail: string, weight: float}>
     */
    private static function trackingItems(?MarketingSetting $marketing): array
    {
        $ga4Setting = trim((string) ($marketing?->ga4_measurement_id ?? ''));
        $pixelSetting = trim((string) ($marketing?->meta_pixel_id ?? ''));

        $gtm = self::integrationValue('google_tag_manager', 'container_id');
        $ga4Int = self::integrationValue('google_analytics', 'measurement_id');
        $pixelInt = self::integrationValue('meta_ads', 'pixel_id');
        $capiPixel = self::integrationValue('meta_capi', 'capi_pixel_id');
        $capiToken = self::integrationValue('meta_capi', 'capi_access_token');
        $clarity = self::integrationValue('microsoft_clarity', 'project_id');

        $ga4 = $ga4Setting !== '' ? $ga4Setting : $ga4Int;
        $pixel = $pixelSetting !== '' ? $pixelSetting : $pixelInt;

        $hasTags = $gtm !== '' || $ga4 !== '' || $pixel !== '';
        $prod = app()->environment('production');

        if ($hasTags) {
            $coverageStatus = 'pass';
            $coverageDetail = 'At least one measurement surface is configured (GTM, GA4, or Meta).';
        } elseif ($prod) {
            $coverageStatus = 'fail';
            $coverageDetail = 'Production has no GTM, GA4, or Meta Pixel — public traffic will not be measured.';
        } else {
            $coverageStatus = 'warn';
            $coverageDetail = 'No analytics tags configured yet (typical for local). Enable before launch.';
        }

        if (! $hasTags) {
            $tagsBundleStatus = 'pass';
            $tagsBundleDetail = 'Container IDs are optional until you enable measurement on the public site.';
        } elseif ($gtm !== '') {
            $tagsBundleStatus = 'pass';
            $tagsBundleDetail = 'Google Tag Manager container ID is set (preferred single container).';
        } elseif ($ga4 !== '' || $pixel !== '') {
            $tagsBundleStatus = 'pass';
            $tagsBundleDetail = 'Direct GA4 and/or Meta Pixel IDs are configured.';
        } else {
            $tagsBundleStatus = 'fail';
            $tagsBundleDetail = 'Incomplete tag configuration.';
        }

        if ($gtm !== '' && ($ga4 !== '' || $pixel !== '')) {
            $dupStatus = 'warn';
            $dupDetail = 'GTM is set alongside direct GA4/Meta IDs — avoid double-counting; prefer tags only inside GTM.';
        } else {
            $dupStatus = 'pass';
            $dupDetail = 'No duplicate direct tags alongside GTM.';
        }

        if ($pixel === '') {
            $capiStatus = 'pass';
            $capiDetail = 'Meta Pixel not configured — CAPI pairing optional unless you add a browser pixel.';
        } elseif ($capiPixel !== '' && $capiToken !== '') {
            $capiStatus = 'pass';
            $capiDetail = 'Meta CAPI pixel ID and access token are set for server-side events.';
        } elseif ($capiPixel !== '' || $capiToken !== '') {
            $capiStatus = 'warn';
            $capiDetail = 'Complete Meta CAPI credentials for reliable ads attribution.';
        } else {
            $capiStatus = 'warn';
            $capiDetail = 'Browser Meta Pixel is set without CAPI — add server events for privacy browsers and deduplication.';
        }

        $clarityStatus = $clarity !== '' ? 'pass' : 'warn';
        $clarityDetail = $clarity !== ''
            ? 'Microsoft Clarity project ID is set.'
            : 'Optional: add Microsoft Clarity under Integrations for session insights.';

        $gemEnv = trim((string) config('gemini.api_key', ''));
        $gemInt = self::integrationValue('gemini', 'api_key');
        $geminiKey = $gemEnv !== '' ? $gemEnv : $gemInt;
        $gemStatus = $geminiKey !== '' ? 'pass' : 'warn';
        $gemDetail = $geminiKey !== ''
            ? 'Gemini API key is available (config or Integrations).'
            : 'Optional: set GEMINI_API_KEY or enable the Gemini integration for AI-backed tools.';

        return [
            ['label' => 'Public measurement', 'status' => $coverageStatus, 'detail' => $coverageDetail, 'weight' => 2.0],
            ['label' => 'GTM / GA4 / Meta Pixel', 'status' => $tagsBundleStatus, 'detail' => $tagsBundleDetail, 'weight' => 3.0],
            ['label' => 'Tag duplication', 'status' => $dupStatus, 'detail' => $dupDetail, 'weight' => 1.0],
            ['label' => 'Meta Conversions API', 'status' => $capiStatus, 'detail' => $capiDetail, 'weight' => 2.0],
            ['label' => 'Microsoft Clarity', 'status' => $clarityStatus, 'detail' => $clarityDetail, 'weight' => 1.0],
            ['label' => 'Gemini API', 'status' => $gemStatus, 'detail' => $gemDetail, 'weight' => 1.0],
        ];
    }

    /**
     * @param  list<array{label: string, status: string, detail: string, weight: float}>  $seoItems
     * @param  list<array{label: string, status: string, detail: string, weight: float}>  $trackingItems
     * @return list<array{priority: string, text: string, href: string|null}>
     */
    private static function suggestionsFromItems(array $seoItems, array $trackingItems): array
    {
        $out = [];
        foreach (array_merge($seoItems, $trackingItems) as $item) {
            if ($item['status'] === 'pass') {
                continue;
            }
            $priority = $item['status'] === 'fail' ? 'high' : 'medium';
            $out[] = [
                'priority' => $priority,
                'text' => $item['label'].': '.$item['detail'],
                'href' => self::fixHrefForLabel((string) $item['label']),
            ];
        }

        if (Route::has('admin.settings.integrations.index')) {
            $out[] = [
                'priority' => 'low',
                'text' => 'Wire GA4, GTM, Meta, Clarity, CAPI, and Gemini in Admin → Settings → Integrations.',
                'href' => route('admin.settings.integrations.index'),
            ];
        }

        $out[] = [
            'priority' => 'low',
            'text' => 'After changing GTM, use Preview mode and GA4 DebugView to confirm Page View and key events.',
            'href' => null,
        ];

        return $out;
    }

    private static function fixHrefForLabel(string $label): ?string
    {
        return match ($label) {
            'Site description', 'Organization name', 'Telephone (E.164)', 'Logo or OG image', 'Entity links (sameAs)',
            'Street address', 'Postal code' => self::hrefEntity(),
            'Google Search Console verification', 'AI discovery flags', 'Bing Webmaster integration' => self::hrefTechnical(),
            'Hub coordinates (GEO)' => self::hrefGeo(),
            'HTTPS (APP_URL)' => null,
            'Public measurement', 'GTM / GA4 / Meta Pixel', 'Tag duplication', 'Meta Conversions API', 'Microsoft Clarity', 'Gemini API' => self::hrefIntegrations(),
            default => self::hrefMarketing(),
        };
    }

    private static function hrefEntity(): string
    {
        return Route::has('growth-center.seo.entity') ? route('growth-center.seo.entity') : '#';
    }

    private static function hrefTechnical(): string
    {
        return Route::has('growth-center.seo.technical') ? route('growth-center.seo.technical') : '#';
    }

    private static function hrefGeo(): string
    {
        return Route::has('growth-center.geo.location') ? route('growth-center.geo.location') : '#';
    }

    private static function hrefMarketing(): string
    {
        return Route::has('modules.marketing') ? route('modules.marketing') : '#';
    }

    private static function hrefIntegrations(): ?string
    {
        if (Route::has('admin.settings.integrations.index')) {
            return route('admin.settings.integrations.index');
        }

        return null;
    }
}
