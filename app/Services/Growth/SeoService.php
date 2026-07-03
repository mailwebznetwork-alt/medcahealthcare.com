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
use App\Support\SeoTechnicalResolver;
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
                'name' => config('medca.brand_name', 'Karnataka Diagnostic Centre'),
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
                'llms_txt' => $data['llms_txt'] ?? ($data['llm_txt'] ?? null),
                'ai_discovery_enabled' => (bool) ($data['ai_discovery_enabled'] ?? true),
                'google_site_verification' => $data['google_site_verification'] ?? null,
                'bing_site_verification' => $data['bing_site_verification'] ?? null,
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

        $technical = SeoTechnicalResolver::forCurrentSite();

        if ($technical === null) {
            return true;
        }

        return (bool) $technical->sitemap_enabled;
    }

    public function generateRobots(): string
    {
        $technical = SeoTechnicalResolver::forCurrentSite(repairOrphan: false);
        $robots = trim((string) ($technical?->robots_txt ?? ''));

        if ($robots !== '') {
            return $robots;
        }

        $lines = $this->defaultRobotsAiAgentLines();

        $lines[] = 'User-agent: *';
        $lines[] = 'Allow: /';
        foreach ($this->defaultRobotsDisallowPaths() as $path) {
            $lines[] = 'Disallow: '.$path;
        }
        $lines[] = 'Sitemap: '.url('/sitemap.xml');
        $lines[] = '# LLM discovery: '.url('/llms.txt');

        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    protected function defaultRobotsDisallowPaths(): array
    {
        return [
            '/console',
            '/site-architect',
            '/operations',
            '/marketing',
            '/growth-center',
            '/user-management',
            '/security',
            '/system',
            '/settings',
            '/login',
            '/register',
            '/profile',
            '/livewire',
            '/api/',
        ];
    }

    /**
     * @return list<string>
     */
    protected function defaultRobotsAiAgentLines(): array
    {
        if ($this->shouldAllowAiCrawlers()) {
            return $this->aiCrawlerAllowLines();
        }

        return $this->aiCrawlerDisallowLines();
    }

    protected function shouldAllowAiCrawlers(): bool
    {
        if (Schema::hasTable('seo_ai_signals')) {
            $signal = \App\Models\SeoAiSignal::query()->latest('id')->first();
            if ($signal !== null) {
                return (bool) $signal->ai_crawl_enabled;
            }
        }

        $technical = SeoTechnicalResolver::forCurrentSite(repairOrphan: false);

        return $technical !== null && (bool) $technical->ai_discovery_enabled;
    }

    /**
     * @return list<string>
     */
    protected function aiCrawlerAllowLines(): array
    {
        $lines = [];
        foreach ($this->aiCrawlerUserAgents() as $agent) {
            $lines[] = 'User-agent: '.$agent;
            $lines[] = 'Allow: /';
            $lines[] = '';
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    protected function aiCrawlerDisallowLines(): array
    {
        $lines = [];
        foreach ($this->aiCrawlerUserAgents() as $agent) {
            $lines[] = 'User-agent: '.$agent;
            $lines[] = 'Disallow: /';
            $lines[] = '';
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    protected function aiCrawlerUserAgents(): array
    {
        return [
            'GPTBot',
            'ChatGPT-User',
            'Google-Extended',
            'ClaudeBot',
            'anthropic-ai',
            'PerplexityBot',
            'CCBot',
            'Bytespider',
            'Applebot-Extended',
            'cohere-ai',
            'Diffbot',
            'FacebookBot',
            'meta-externalagent',
        ];
    }

    /**
     * Sitemap index pointing at segment urlsets (blogs, services, images, core pages).
     */
    public function generateSitemapIndex(): string
    {
        $files = $this->sitemapIndexFiles();

        $entries = $files->map(function (string $file): string {
            $loc = e(url('/'.$file));
            $lastmod = e($this->formatSitemapLastmod(now()));

            return "    <sitemap>\n        <loc>{$loc}</loc>\n        <lastmod>{$lastmod}</lastmod>\n    </sitemap>";
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
        $total = $this->countIndexableLocationPaths();
        $chunk = max(1, (int) config('sitemap.location_chunk_size', 10000));

        return max(1, (int) ceil($total / $chunk));
    }

    public function generateLocationChunkSitemapXml(int $chunk): string
    {
        $chunkSize = max(1, (int) config('sitemap.location_chunk_size', 10000));
        $offset = ($chunk - 1) * $chunkSize;

        if (! Schema::hasTable('service_location_pages')) {
            return $this->buildStandardUrlsetXml(collect());
        }

        $entries = ServiceLocationPage::query()
            ->whereNotNull('location_slug')
            ->where('is_indexable', true)
            ->with([
                'service:id,service_code,is_active,publish_status,visibility',
                'page:id,is_active,robots_meta',
                'pincode:id,pincode',
            ])
            ->orderBy('id')
            ->offset($offset)
            ->limit($chunkSize)
            ->get()
            ->filter(fn (ServiceLocationPage $row): bool => $row->isPubliclyIndexable())
            ->map(function (ServiceLocationPage $row): array {
                if (config('services_master.public_url_include_pincode', false) && $row->pincode !== null) {
                    $city = $row->city_slug ?: app(\App\Services\Operations\ServicePublicUrlBuilder::class)->citySlugForPin($row->pincode);
                    $path = '/services/'.$row->service->service_code.'/'.$city.'/'.$row->pincode->pincode;
                } else {
                    $path = '/services/'.$row->service->service_code.'/'.$row->location_slug;
                }

                return [
                    'path' => $path,
                    'lastmod' => $row->updated_at,
                ];
            })
            ->unique('path')
            ->values();

        return $this->buildStandardUrlsetXml($entries);
    }

    public function generateStaticPagesSitemapXml(): string
    {
        return $this->buildStandardUrlsetXml($this->collectStaticPageEntries());
    }

    public function generateServiceDetailsSitemapXml(): string
    {
        if (! Schema::hasTable('services')) {
            return $this->buildStandardUrlsetXml(collect());
        }

        $entries = collect(
            Service::query()
                ->where('is_active', true)
                ->where('publish_status', PublishStatus::Published)
                ->where('visibility', ServiceVisibility::Public)
                ->orderBy('service_code')
                ->get(['service_code', 'updated_at'])
                ->map(fn (Service $service): array => [
                    'path' => '/services/'.$service->service_code,
                    'lastmod' => $service->updated_at,
                ])
        );

        if (Schema::hasTable('page_seo')) {
            $entries = $entries->concat(
                PageSeo::query()
                    ->whereNotNull('page_slug')
                    ->where('page_slug', 'like', 'services/%')
                    ->get(['page_slug', 'updated_at'])
                    ->map(fn (PageSeo $row): array => [
                        'path' => '/'.ltrim((string) $row->page_slug, '/'),
                        'lastmod' => $row->updated_at,
                    ])
            );
        }

        return $this->buildStandardUrlsetXml($entries->unique('path')->values());
    }

    public function generateCategoriesSitemapXml(): string
    {
        if (! Schema::hasTable('service_categories')) {
            return $this->buildStandardUrlsetXml(collect());
        }

        $entries = ServiceCategory::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['code', 'updated_at'])
            ->map(fn (ServiceCategory $category): array => [
                'path' => '/service-categories/'.$category->code,
                'lastmod' => $category->updated_at,
            ]);

        return $this->buildStandardUrlsetXml($entries);
    }

    public function generateSubservicesSitemapXml(): string
    {
        if (! Schema::hasTable('sub_services') || ! Schema::hasTable('services')) {
            return $this->buildStandardUrlsetXml(collect());
        }

        $entries = SubService::query()
            ->publicListing()
            ->with('service')
            ->get()
            ->filter(fn (SubService $sub): bool => $sub->service !== null && $sub->service->isListedPublicly())
            ->map(function (SubService $sub): array {
                $url = $sub->publicUrl();
                $path = parse_url($url, PHP_URL_PATH) ?: $url;

                return [
                    'path' => $path,
                    'lastmod' => $sub->updated_at,
                ];
            })
            ->unique('path')
            ->values();

        return $this->buildStandardUrlsetXml($entries);
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
            ->orderBy('slug')
            ->get(['slug', 'updated_at'])
            ->map(fn (Blog $blog): array => [
                'path' => '/blog/'.$blog->slug,
                'lastmod' => $blog->updated_at,
            ]);

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
                ->select(['slug', 'featured_image', 'title', 'updated_at'])
                ->each(function (Blog $blog) use (&$blocks): void {
                    $imageUrl = $this->absolutePublicImageUrl($blog->featured_image);
                    if ($imageUrl === null) {
                        return;
                    }
                    $pageLoc = e(url('/blog/'.$blog->slug));
                    $imageLoc = e($imageUrl);
                    $title = e((string) ($blog->title ?? ''));
                    $lastmod = $this->formatSitemapLastmod($blog->updated_at);
                    $lastmodXml = $lastmod !== null ? "\n        <lastmod>".e($lastmod).'</lastmod>' : '';
                    $blocks[] = "    <url>\n        <loc>{$pageLoc}</loc>{$lastmodXml}\n        <image:image>\n            <image:loc>{$imageLoc}</image:loc>\n            <image:title>{$title}</image:title>\n        </image:image>\n    </url>";
                });
        }

        if (Schema::hasTable('pages')) {
            Page::query()
                ->where('is_active', true)
                ->select(['slug', 'og_image', 'og_image_alt', 'title', 'updated_at'])
                ->each(function (Page $page) use (&$blocks): void {
                    $imageUrl = $this->absolutePublicImageUrl($page->og_image);
                    if ($imageUrl === null) {
                        return;
                    }
                    $pageLoc = e($page->publicUrl());
                    $imageLoc = e($imageUrl);
                    $title = e((string) ($page->og_image_alt ?: $page->title ?? ''));
                    $lastmod = $this->formatSitemapLastmod($page->updated_at);
                    $lastmodXml = $lastmod !== null ? "\n        <lastmod>".e($lastmod).'</lastmod>' : '';
                    $blocks[] = "    <url>\n        <loc>{$pageLoc}</loc>{$lastmodXml}\n        <image:image>\n            <image:loc>{$imageLoc}</image:loc>\n            <image:title>{$title}</image:title>\n        </image:image>\n    </url>";
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
     * @param  Collection<int, array{path: string, lastmod?: \DateTimeInterface|string|null}|string>  $entries
     */
    protected function buildStandardUrlsetXml(Collection $entries): string
    {
        $normalized = $entries
            ->map(function (array|string $entry): array {
                if (is_string($entry)) {
                    return ['path' => $entry, 'lastmod' => null];
                }

                return [
                    'path' => (string) ($entry['path'] ?? ''),
                    'lastmod' => $entry['lastmod'] ?? null,
                ];
            })
            ->filter(fn (array $entry): bool => $entry['path'] !== '')
            ->map(function (array $entry): array {
                $path = str_starts_with($entry['path'], '/') ? $entry['path'] : '/'.$entry['path'];

                return ['path' => $path, 'lastmod' => $entry['lastmod']];
            })
            ->unique('path')
            ->values();

        $entriesXml = $normalized->map(function (array $entry): string {
            $location = e(url($entry['path']));
            $lastmod = $this->formatSitemapLastmod($entry['lastmod']);
            $lastmodXml = $lastmod !== null ? "\n        <lastmod>".e($lastmod).'</lastmod>' : '';

            return "    <url>\n        <loc>{$location}</loc>{$lastmodXml}\n    </url>";
        })->implode("\n");

        return implode("\n", [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
            $entriesXml,
            '</urlset>',
        ]);
    }

    protected function formatSitemapLastmod(\DateTimeInterface|string|null $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = $value instanceof \DateTimeInterface
            ? \Illuminate\Support\Carbon::instance($value)
            : \Illuminate\Support\Carbon::parse($value);

        return $date->utc()->format('Y-m-d\TH:i:s\Z');
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
        if (config('sitemap.paginated_enabled', true)) {
            return collect();
        }

        if (! Schema::hasTable('service_location_pages')) {
            return collect();
        }

        $paths = collect();
        ServiceLocationPage::query()
            ->whereNotNull('location_slug')
            ->where('is_indexable', true)
            ->with([
                'service:id,service_code,is_active,publish_status,visibility',
                'page:id,is_active,robots_meta',
                'pincode:id,pincode',
            ])
            ->orderBy('id')
            ->lazyById(250)
            ->each(function (ServiceLocationPage $row) use (&$paths): void {
                if (! $row->isPubliclyIndexable()) {
                    return;
                }

                if (config('services_master.public_url_include_pincode', false) && $row->pincode !== null) {
                    $city = $row->city_slug ?: app(\App\Services\Operations\ServicePublicUrlBuilder::class)->citySlugForPin($row->pincode);
                    $paths->push('/services/'.$row->service->service_code.'/'.$city.'/'.$row->pincode->pincode);

                    return;
                }

                $paths->push('/services/'.$row->service->service_code.'/'.$row->location_slug);
            });

        return $paths->unique()->values();
    }

    protected function countIndexableLocationPaths(): int
    {
        if (! Schema::hasTable('service_location_pages')) {
            return 0;
        }

        return ServiceLocationPage::query()
            ->whereNotNull('location_slug')
            ->where('is_indexable', true)
            ->whereHas('service', fn ($query) => $query->publicListing())
            ->whereHas('page', fn ($query) => $query->where('is_active', true))
            ->count();
    }

    /**
     * @return Collection<int, string>
     */
    protected function collectLocationPathsSlice(int $offset, int $limit): Collection
    {
        if (! Schema::hasTable('service_location_pages')) {
            return collect();
        }

        return ServiceLocationPage::query()
            ->whereNotNull('location_slug')
            ->where('is_indexable', true)
            ->with([
                'service:id,service_code,is_active,publish_status,visibility',
                'page:id,is_active,robots_meta',
                'pincode:id,pincode',
            ])
            ->orderBy('id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->filter(fn (ServiceLocationPage $row): bool => $row->isPubliclyIndexable())
            ->map(function (ServiceLocationPage $row): string {
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
     * @return Collection<int, array{path: string, lastmod?: \DateTimeInterface|null}>
     */
    protected function collectStaticPageEntries(): Collection
    {
        $entries = collect([
            ['path' => '/', 'lastmod' => $this->pageLastmodForSlug('home')],
            ['path' => '/about-us', 'lastmod' => $this->pageLastmodForSlug('about-us')],
            ['path' => '/contact', 'lastmod' => $this->pageLastmodForSlug('contact')],
            ['path' => '/services-catalog', 'lastmod' => $this->pageLastmodForSlug('services-catalog')],
        ]);

        if (Schema::hasTable('page_seo')) {
            $entries = $entries->concat(
                PageSeo::query()
                    ->whereNotNull('page_slug')
                    ->where('page_slug', 'not like', 'services/%')
                    ->get(['page_slug', 'updated_at'])
                    ->map(fn (PageSeo $row): array => [
                        'path' => '/'.ltrim((string) $row->page_slug, '/'),
                        'lastmod' => $row->updated_at,
                    ])
            );
        }

        if (Schema::hasTable('pages')) {
            $entries = $entries->concat(
                Page::query()
                    ->where('is_active', true)
                    ->get(['slug', 'updated_at'])
                    ->reject(fn (Page $page): bool => \App\Services\Operations\ServiceDetailPageProvisioner::serviceCodeFromPageSlug($page->slug) !== null
                        || str_contains($page->slug, '-loc-'))
                    ->map(fn (Page $page): array => [
                        'path' => Page::publicPathForSlug($page->slug),
                        'lastmod' => $page->updated_at,
                    ])
            );
        }

        if (Schema::hasTable('pin_codes')) {
            $entries = $entries->concat(
                PinCode::query()
                    ->whereNotNull('landing_page')
                    ->get(['landing_page', 'updated_at'])
                    ->map(fn (PinCode $pin): array => [
                        'path' => (string) $pin->landing_page,
                        'lastmod' => $pin->updated_at,
                    ])
            );
        }

        return $entries->unique('path')->values();
    }

    protected function pageLastmodForSlug(string $slug): ?\DateTimeInterface
    {
        if (! Schema::hasTable('pages')) {
            return null;
        }

        return Page::query()->where('slug', $slug)->value('updated_at');
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
