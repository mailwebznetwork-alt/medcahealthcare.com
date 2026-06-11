<?php

namespace App\Services\Growth;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Blog;
use App\Models\BusinessProfile;
use App\Models\PinCode;
use App\Models\Page;
use App\Models\PageSeo;
use App\Models\SeoEntity;
use App\Models\SeoTechnical;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SeoService
{
    public function ensureBusinessProfile(): BusinessProfile
    {
        return BusinessProfile::query()->firstOrCreate(
            ['website' => config('app.url')],
            [
                'name' => config('medca.brand_name', 'Medca Health Care'),
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

    /**
     * Sitemap index pointing at segment urlsets (blogs, services, images, core pages).
     */
    public function generateSitemapIndex(): string
    {
        $files = $this->sitemapIndexFiles();

        $entries = $files->map(function (string $file): string {
            $loc = e(url('/'.$file));

            return "    <sitemap>\n        <loc>{$loc}</loc>\n    </sitemap>";
        })->implode("\n");

        return implode("\n", [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
            $entries,
            '</sitemapindex>',
        ]);
    }

    /**
     * @return Collection<int, string>
     */
    public function sitemapIndexFiles(): Collection
    {
        if (config('sitemap.paginated_enabled', true)) {
            $files = collect([
                'sitemap-static-pages.xml',
                'sitemap-blogs.xml',
                'sitemap-services.xml',
                'sitemap-categories.xml',
                'sitemap-subservices.xml',
                'sitemap-images.xml',
            ]);

            for ($i = 1; $i <= $this->locationSitemapChunkCount(); $i++) {
                $files->push(sprintf('sitemap-locations-%03d.xml', $i));
            }

            return $files->values();
        }

        return collect(['sitemap-pages.xml', 'sitemap-blogs.xml', 'sitemap-services.xml', 'sitemap-images.xml']);
    }

    public function locationSitemapChunkCount(): int
    {
        $total = $this->collectLocationPaths()->count();
        $chunk = max(1, (int) config('sitemap.location_chunk_size', 10000));

        return max(1, (int) ceil($total / $chunk));
    }

    public function generateStaticPagesSitemapXml(): string
    {
        return $this->buildStandardUrlsetXml($this->collectStaticPagePaths());
    }

    public function generateServiceDetailsSitemapXml(): string
    {
        return $this->buildStandardUrlsetXml(
            $this->collectServiceDetailPaths()
                ->merge($this->legacyGrowthServicePaths())
                ->unique()
                ->values()
        );
    }

    public function generateCategoriesSitemapXml(): string
    {
        return $this->buildStandardUrlsetXml($this->collectCategoryPaths());
    }

    public function generateSubservicesSitemapXml(): string
    {
        return $this->buildStandardUrlsetXml($this->collectSubServicePaths());
    }

    public function generateLocationChunkSitemapXml(int $chunk): string
    {
        $chunkSize = max(1, (int) config('sitemap.location_chunk_size', 10000));
        $offset = ($chunk - 1) * $chunkSize;
        $paths = $this->collectLocationPaths()->slice($offset, $chunkSize)->values();

        return $this->buildStandardUrlsetXml($paths);
    }

    /**
     * Core public URLs: home, static hints, Growth page SEO (non-service slugs), CMS pages, geo landing pages.
     */
    public function generatePagesSitemapXml(): string
    {
        return $this->buildStandardUrlsetXml($this->collectPagesSegmentPaths());
    }

    /**
     * Published blog posts (same visibility rules as the public blog route).
     */
    public function generateBlogsSitemapXml(): string
    {
        if (! Schema::hasTable('blogs')) {
            return $this->buildStandardUrlsetXml(collect());
        }

        $paths = Blog::query()
            ->where('is_published', true)
            ->where(function ($query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->pluck('slug')
            ->map(fn (string $slug): string => '/blog/'.$slug);

        return $this->buildStandardUrlsetXml($paths);
    }

    /**
     * Service-oriented URLs from Growth Center page slugs under the services/ path prefix.
     */
    public function generateServicesSitemapXml(): string
    {
        return $this->buildStandardUrlsetXml($this->collectServiceSegmentPaths());
    }

    /**
     * Image extension for pages and blogs that expose publicly reachable artwork (OG / featured).
     */
    public function generateImagesSitemapXml(): string
    {
        $blocks = [];

        if (Schema::hasTable('blogs')) {
            Blog::query()
                ->where('is_published', true)
                ->where(function ($query): void {
                    $query->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                })
                ->select(['slug', 'featured_image', 'title'])
                ->each(function (Blog $blog) use (&$blocks): void {
                    $imageUrl = $this->absolutePublicImageUrl($blog->featured_image);
                    if ($imageUrl === null) {
                        return;
                    }
                    $pageLoc = e(url('/blog/'.$blog->slug));
                    $imageLoc = e($imageUrl);
                    $title = e((string) ($blog->title ?? ''));
                    $blocks[] = "    <url>\n        <loc>{$pageLoc}</loc>\n        <image:image>\n            <image:loc>{$imageLoc}</image:loc>\n            <image:title>{$title}</image:title>\n        </image:image>\n    </url>";
                });
        }

        if (Schema::hasTable('pages')) {
            Page::query()
                ->where('is_active', true)
                ->select(['slug', 'og_image', 'og_image_alt', 'title'])
                ->each(function (Page $page) use (&$blocks): void {
                    $imageUrl = $this->absolutePublicImageUrl($page->og_image);
                    if ($imageUrl === null) {
                        return;
                    }
                    $pageLoc = e($page->publicUrl());
                    $imageLoc = e($imageUrl);
                    $title = e((string) ($page->og_image_alt ?: $page->title ?? ''));
                    $blocks[] = "    <url>\n        <loc>{$pageLoc}</loc>\n        <image:image>\n            <image:loc>{$imageLoc}</image:loc>\n            <image:title>{$title}</image:title>\n        </image:image>\n    </url>";
                });
        }

        $body = $blocks === [] ? '' : implode("\n", $blocks);

        return implode("\n", [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"',
            '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">',
            $body,
            '</urlset>',
        ]);
    }

    /**
     * @param  Collection<int, string>  $paths
     */
    protected function buildStandardUrlsetXml(Collection $paths): string
    {
        $urls = $paths
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
    protected function collectPagesSegmentPaths(): Collection
    {
        return collect(['/', '/about-us', '/contact', '/services-catalog'])
            ->merge($this->pageLevelUrlsExcludingServicesPrefix())
            ->merge($this->cmsPageUrls())
            ->merge($this->geoLevelUrls());
    }

    /**
     * @return Collection<int, string>
     */
    protected function collectServiceSegmentPaths(): Collection
    {
        return $this->collectServiceDetailPaths()
            ->merge($this->collectLocationPaths())
            ->merge($this->legacyGrowthServicePaths())
            ->unique()
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    protected function collectServiceDetailPaths(): Collection
    {
        if (! Schema::hasTable('services')) {
            return collect();
        }

        return Service::query()
            ->where('is_active', true)
            ->where('publish_status', PublishStatus::Published)
            ->where('visibility', ServiceVisibility::Public)
            ->pluck('service_code')
            ->map(fn (string $code): string => '/services/'.$code)
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    protected function collectLocationPaths(): Collection
    {
        if (! Schema::hasTable('service_location_pages')) {
            return collect();
        }

        return ServiceLocationPage::query()
            ->whereNotNull('location_slug')
            ->where('is_indexable', true)
            ->with(['service', 'page', 'pincode'])
            ->get()
            ->filter(fn (ServiceLocationPage $row): bool => $row->isPubliclyIndexable())
            ->map(function (ServiceLocationPage $row): string {
                $row->loadMissing(['service', 'pincode']);

                if (config('services_master.public_url_include_pincode', false) && $row->pincode !== null) {
                    $city = $row->city_slug ?: app(\App\Services\Operations\ServicePublicUrlBuilder::class)->citySlugForPin($row->pincode);

                    return '/services/'.$row->service->service_code.'/'.$city.'/'.$row->pincode->pincode;
                }

                return '/services/'.$row->service->service_code.'/'.$row->location_slug;
            })
            ->unique()
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    protected function collectCategoryPaths(): Collection
    {
        if (! Schema::hasTable('service_categories')) {
            return collect();
        }

        return ServiceCategory::query()
            ->where('is_active', true)
            ->pluck('code')
            ->map(fn (string $code): string => '/service-categories/'.$code)
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    protected function collectSubServicePaths(): Collection
    {
        if (! Schema::hasTable('sub_services') || ! Schema::hasTable('services')) {
            return collect();
        }

        return SubService::query()
            ->publicListing()
            ->with('service')
            ->get()
            ->filter(fn (SubService $sub): bool => $sub->service !== null && $sub->service->isListedPublicly())
            ->map(fn (SubService $sub): string => $sub->publicUrl())
            ->map(fn (string $url): string => parse_url($url, PHP_URL_PATH) ?: $url)
            ->unique()
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    protected function collectStaticPagePaths(): Collection
    {
        return collect(['/', '/about-us', '/contact', '/services-catalog'])
            ->merge($this->pageLevelUrlsExcludingServicesPrefix())
            ->merge($this->cmsPageUrls())
            ->merge($this->geoLevelUrls())
            ->unique()
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    protected function legacyGrowthServicePaths(): Collection
    {
        if (! Schema::hasTable('page_seo')) {
            return collect();
        }

        return PageSeo::query()
            ->whereNotNull('page_slug')
            ->where('page_slug', 'like', 'services/%')
            ->pluck('page_slug');
    }

    /**
     * @return Collection<int, string>
     */
    protected function pageLevelUrlsExcludingServicesPrefix(): Collection
    {
        if (! Schema::hasTable('page_seo')) {
            return collect();
        }

        return PageSeo::query()
            ->whereNotNull('page_slug')
            ->where('page_slug', 'not like', 'services/%')
            ->pluck('page_slug');
    }

    protected function absolutePublicImageUrl(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $trim = trim($value);

        if (Str::startsWith($trim, ['http://', 'https://'])) {
            return $trim;
        }

        $relative = ltrim($trim, '/');

        return url(Storage::disk('public')->url($relative));
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
            ->reject(fn (string $slug): bool => \App\Services\Operations\ServiceDetailPageProvisioner::serviceCodeFromPageSlug($slug) !== null
                || str_contains($slug, '-loc-'))
            ->map(fn (string $slug): string => Page::publicPathForSlug($slug));
    }

    /**
     * @return Collection<int, string>
     */
    protected function geoLevelUrls(): Collection
    {
        if (! Schema::hasTable('pin_codes')) {
            return collect();
        }

        return PinCode::query()
            ->whereNotNull('landing_page')
            ->pluck('landing_page');
    }
}
