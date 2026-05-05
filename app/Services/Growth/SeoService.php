<?php

namespace App\Services\Growth;

use App\Models\BusinessProfile;
use App\Models\PageSeo;
use App\Models\Pincode;
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

        return SeoEntity::query()->updateOrCreate(
            ['business_profile_id' => $profile->id],
            [
                'organization_name' => $data['organization_name'],
                'logo' => $data['logo'] ?? null,
                'same_as' => $data['same_as'] ?? [],
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
            ]
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
            ]
        );
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
    protected function geoLevelUrls(): Collection
    {
        if (! Schema::hasTable('pincodes')) {
            return collect();
        }

        return Pincode::query()
            ->whereNotNull('landing_page')
            ->pluck('landing_page');
    }
}
