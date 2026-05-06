<?php

namespace App\Services\Growth;

use App\Models\BusinessProfile;
use App\Models\GrowthPincode;
use App\Models\Page;
use App\Models\PageSeo;
use App\Models\SeoEntity;
use App\Models\SeoTechnical;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SeoService
{
    public function ensureBusinessProfile(): BusinessProfile
    {
        return BusinessProfile::query()->firstOrCreate(
            ['website' => config('app.url')],
            [
                'name' => config('app.name'),
                'email' => config('mail.from.address'),
                'phone' => null,
                'address' => null,
            ]
        );
    }

    public function saveEntity(array $data): SeoEntity
    {
        $profile = $this->ensureBusinessProfile();

        $profile->fill([
            'name' => $data['organization_name'],
            'phone_e164' => $data['phone_e164'] ?? null,
            'country_code' => $data['country_code'] ?? null,
            'street_address' => $data['street_address'] ?? null,
            'city' => $data['city'] ?? null,
            'region' => $data['region'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
        ]);

        if (! empty($data['phone_e164'])) {
            $profile->phone = $data['phone_e164'];
        }

        $addressLines = array_filter([
            $data['street_address'] ?? null,
            trim(implode(', ', array_filter([
                $data['city'] ?? null,
                $data['region'] ?? null,
                $data['postal_code'] ?? null,
            ]))),
        ]);

        if ($addressLines !== []) {
            $profile->address = implode("\n", $addressLines);
        }

        $profile->save();

        $sameAs = array_values(array_unique(array_filter($data['same_as'] ?? [])));
        $gmbProfileUrl = $data['google_business_profile_url'] ?? null;
        if (is_string($gmbProfileUrl) && $gmbProfileUrl !== '' && filter_var($gmbProfileUrl, FILTER_VALIDATE_URL)) {
            if (! in_array($gmbProfileUrl, $sameAs, true)) {
                $sameAs[] = $gmbProfileUrl;
            }
        }

        $entityPayload = [
            'organization_name' => $data['organization_name'],
            'logo' => $data['logo'] ?? null,
            'same_as' => $sameAs,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'og_image_url' => $data['og_image_url'] ?? null,
            'custom_json_ld' => $data['custom_json_ld'] ?? null,
            'google_place_id' => $data['google_place_id'] ?? null,
            'google_business_profile_url' => $data['google_business_profile_url'] ?? null,
            'has_map_url' => $data['has_map_url'] ?? null,
        ];

        if (array_key_exists('entity_faqs', $data)) {
            $entityPayload['entity_faqs'] = $data['entity_faqs'];
        }

        return SeoEntity::query()->updateOrCreate(
            ['business_profile_id' => $profile->id],
            $entityPayload
        );
    }

    public function saveTechnical(array $data): SeoTechnical
    {
        $profile = $this->ensureBusinessProfile();

        return SeoTechnical::query()->updateOrCreate(
            ['business_profile_id' => $profile->id],
            [
                'robots_txt' => $data['robots_txt'] ?? null,
                'sitemap_enabled' => (bool) ($data['sitemap_enabled'] ?? true),
                'canonical_url' => $data['canonical_url'] ?? null,
                'indexable' => (bool) ($data['indexable'] ?? true),
                'llm_txt' => $data['llm_txt'] ?? null,
                'ai_discovery_enabled' => (bool) ($data['ai_discovery_enabled'] ?? true),
                'google_site_verification' => $data['google_site_verification'] ?? null,
            ]
        );
    }

    public function isSitemapPubliclyAvailable(): bool
    {
        if (! Schema::hasTable('seo_technical') || ! Schema::hasTable('business_profiles')) {
            return true;
        }

        $profile = BusinessProfile::query()->where('website', config('app.url'))->first()
            ?? BusinessProfile::query()->latest('id')->first();

        if (! $profile instanceof BusinessProfile) {
            return true;
        }

        $technical = SeoTechnical::query()->where('business_profile_id', $profile->id)->first();

        if (! $technical instanceof SeoTechnical) {
            return true;
        }

        return (bool) $technical->sitemap_enabled;
    }

    public function generateRobots(): string
    {
        $technical = SeoTechnical::query()->latest('id')->first();
        $robots = trim((string) ($technical?->robots_txt ?? ''));

        if ($robots !== '') {
            return $robots;
        }

        return implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Sitemap: /sitemap.xml',
        ]);
    }

    public function generateSitemap(): string
    {
        $urls = collect(['/', '/about', '/contact'])
            ->merge($this->pageLevelUrls())
            ->merge($this->cmsPageUrls())
            ->merge($this->geoLevelUrls())
            ->filter(fn (?string $path): bool => is_string($path) && $path !== '')
            ->map(fn (string $path): string => str_starts_with($path, '/') ? $path : '/'.$path)
            ->unique()
            ->values();

        $entries = $urls->map(function (string $path): string {
            $location = e(url($path));

            return "    <url>\n        <loc>{$location}</loc>\n    </url>";
        })->implode("\n");

        return implode("\n", [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
            $entries,
            '</urlset>',
        ]);
    }

    /**
     * @return Collection<int, string>
     */
    protected function pageLevelUrls(): Collection
    {
        if (! Schema::hasTable('page_seo')) {
            return collect();
        }

        return PageSeo::query()
            ->whereNotNull('page_slug')
            ->pluck('page_slug');
    }

    /**
     * @return Collection<int, string>
     */
    protected function cmsPageUrls(): Collection
    {
        if (! Schema::hasTable('pages')) {
            return collect();
        }

        return Page::query()
            ->where('is_active', true)
            ->pluck('slug')
            ->map(fn (string $slug): string => '/p/'.$slug);
    }

    /**
     * @return Collection<int, string>
     */
    protected function geoLevelUrls(): Collection
    {
        if (! Schema::hasTable('pincodes')) {
            return collect();
        }

        return GrowthPincode::query()
            ->whereNotNull('landing_page')
            ->pluck('landing_page');
    }
}
