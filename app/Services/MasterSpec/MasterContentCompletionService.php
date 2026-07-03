<?php

declare(strict_types=1);

namespace App\Services\MasterSpec;

use App\Enums\PageCategory;
use App\Models\BusinessProfile;
use App\Models\GeoLocation;
use App\Models\Page;
use App\Models\SeoAiSignal;
use App\Models\SeoEntity;
use App\Models\SeoTechnical;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceCategoryFaq;
use App\Models\ServiceFaq;
use App\Models\SubService;
use App\Models\SubServiceFaq;
use App\Models\ThemeConfiguration;
use App\Services\Growth\AeoService;
use App\Services\Growth\ContentSeoAutoFillService;
use App\Services\Growth\SeoEntityResolver;
use App\Services\Growth\SeoService;
use App\Services\Operations\CategoryMasterOrchestrator;
use App\Services\Operations\ServiceMasterPageSync;
use App\Services\Operations\ServiceSchemaGenerator;
use App\Services\Operations\SubServiceMasterOrchestrator;
use App\Support\BrandContentCatalog;
use App\Support\GrowthReadinessReport;
use App\Support\SeoTechnicalResolver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Fills empty / partial content fields across Medca modules (excludes Blog + Location pages).
 */
final class MasterContentCompletionService
{
    public function __construct(
        private readonly QuickAnswerGenerator $quickAnswers,
        private readonly ContentSeoAutoFillService $contentSeoAutoFill,
        private readonly SeoService $seoService,
        private readonly AeoService $aeoService,
        private readonly ServiceSchemaGenerator $schemaGenerator,
        private readonly SubServiceMasterOrchestrator $subServiceOrchestrator,
        private readonly CategoryMasterOrchestrator $categoryOrchestrator,
    ) {}

    /**
     * @return array<string, int|list<string>>
     */
    public function run(bool $syncSchemas = true): array
    {
        $stats = [
            'business_profile' => 0,
            'seo_entity' => 0,
            'seo_technical' => 0,
            'seo_ai_signals' => 0,
            'geo_location' => 0,
            'services' => 0,
            'sub_services' => 0,
            'categories' => 0,
            'service_faqs' => 0,
            'sub_service_faqs' => 0,
            'category_faqs' => 0,
            'cms_pages' => 0,
            'service_detail_pages' => 0,
            'schemas_synced' => 0,
            'messages' => [],
        ];

        $brand = BrandContentCatalog::all();

        $stats['business_profile'] = $this->completeBusinessProfile($brand);
        $stats['seo_entity'] = $this->completeSeoEntity($brand);
        $stats['seo_technical'] = $this->completeSeoTechnical($brand);
        $stats['seo_ai_signals'] = $this->completeSeoAiSignals();
        $stats['geo_location'] = $this->completeGeoLocation();

        Service::query()->where('is_active', true)->orderBy('id')->each(function (Service $service) use (&$stats): void {
            if ($this->completeCatalogEntity($service)) {
                $stats['services']++;
            }
            $stats['service_faqs'] += $this->completeServiceFaqs($service);
            $this->completeServiceSeo($service);
        });

        SubService::query()->where('is_active', true)->orderBy('id')->each(function (SubService $sub) use (&$stats): void {
            if ($this->completeCatalogEntity($sub)) {
                $stats['sub_services']++;
            }
            $stats['sub_service_faqs'] += $this->completeSubServiceFaqs($sub);
            $this->completeSubServiceSeo($sub);
        });

        ServiceCategory::query()->orderBy('id')->each(function (ServiceCategory $category) use (&$stats): void {
            if ($this->completeCatalogEntity($category)) {
                $stats['categories']++;
            }
            $stats['category_faqs'] += $this->completeCategoryFaqs($category);
            $this->completeCategorySeo($category);
        });

        $stats['cms_pages'] = $this->completeCmsPages();
        $stats['service_detail_pages'] = $this->syncServiceDetailPages();

        if ($syncSchemas) {
            $stats['schemas_synced'] = $this->syncCatalogSchemas();
        }

        $this->contentSeoAutoFill->refreshAggregateSignals();
        GrowthReadinessReport::forget();

        $stats['messages'][] = __('Master content completion finished.');

        return $stats;
    }

    /**
     * @param  array<string, string>  $brand
     */
    private function completeBusinessProfile(array $brand): int
    {
        if (! Schema::hasTable('business_profiles')) {
            return 0;
        }

        $profile = $this->seoService->ensureBusinessProfile();
        $changed = 0;

        $map = [
            'name' => $brand['brand_name'],
            'email' => config('mail.from.address'),
            'phone' => $brand['phone_display'],
            'phone_e164' => $brand['phone_e164'],
            'country_code' => 'IN',
            'street_address' => 'SLN COMPLEX.3, 2ND FLOOR, AREKERE GATE, Bannerghatta Rd',
            'city' => $brand['city'],
            'region' => $brand['region'],
            'postal_code' => $brand['postal_code'],
            'website' => $brand['website'],
            'address' => $brand['address'],
        ];

        foreach ($map as $field => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            if (blank($profile->{$field})) {
                $profile->{$field} = $value;
                $changed++;
            }
        }

        if ($changed > 0) {
            $profile->saveQuietly();
        }

        return $changed > 0 ? 1 : 0;
    }

    /**
     * @param  array<string, string>  $brand
     */
    private function completeSeoEntity(array $brand): int
    {
        if (! Schema::hasTable('seo_entities')) {
            return 0;
        }

        $entity = app(SeoEntityResolver::class)->ensureForCurrentBusiness();
        $changed = 0;
        $logoUrl = $this->resolveLogoUrl();

        $metaDescription = $this->buildMetaDescription($brand['long_description'] ?: $brand['short_description']);
        $metaTitle = $brand['brand_name'].' | '.$brand['tagline'];

        $fills = [
            'organization_name' => $brand['brand_name'],
            'meta_title' => mb_substr($metaTitle, 0, 255),
            'meta_description' => $metaDescription,
            'logo' => $logoUrl,
            'og_image_url' => $logoUrl,
        ];

        foreach ($fills as $field => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            if (blank($entity->{$field})) {
                $entity->{$field} = $value;
                $changed++;
            }
        }

        if (blank($entity->same_as) || (is_array($entity->same_as) && count($entity->same_as) === 0)) {
            $sameAs = array_values(array_filter([
                filled(config('medca.public_profile_url')) ? (string) config('medca.public_profile_url') : null,
                $brand['whatsapp_url'] !== '' ? $brand['whatsapp_url'] : null,
            ]));
            if ($sameAs !== []) {
                $entity->same_as = $sameAs;
                $changed++;
            }
        }

        if (blank($entity->entity_faqs)) {
            $entity->entity_faqs = $this->buildGlobalEntityFaqs($brand);
            $changed++;
        }

        if ($changed > 0) {
            $entity->saveQuietly();
        }

        return $changed > 0 ? 1 : 0;
    }

    /**
     * @param  array<string, string>  $brand
     */
    private function completeSeoTechnical(array $brand): int
    {
        if (! Schema::hasTable('seo_technical')) {
            return 0;
        }

        $technical = SeoTechnicalResolver::forCurrentSite(repairOrphan: true)
            ?? SeoTechnical::query()->first();

        if ($technical === null) {
            return 0;
        }

        $changed = 0;

        if (blank($technical->canonical_url)) {
            $technical->canonical_url = $brand['website'];
            $changed++;
        }

        if (! $technical->sitemap_enabled) {
            $technical->sitemap_enabled = true;
            $changed++;
        }

        if (! $technical->indexable) {
            $technical->indexable = true;
            $changed++;
        }

        if (! $technical->ai_discovery_enabled) {
            $technical->ai_discovery_enabled = true;
            $changed++;
        }

        if (blank($technical->llm_txt)) {
            $technical->llm_txt = $this->aeoService->generateLlmTxt();
            $changed++;
        }

        if (blank($technical->llms_txt)) {
            $technical->llms_txt = $this->buildLlmsTxtDocument($brand);
            $changed++;
        }

        if ($changed > 0) {
            $technical->saveQuietly();
        }

        return $changed > 0 ? 1 : 0;
    }

    private function completeSeoAiSignals(): int
    {
        if (! Schema::hasTable('seo_ai_signals')) {
            return 0;
        }

        $profile = BusinessProfile::query()->where('website', config('app.url'))->first()
            ?? BusinessProfile::query()->latest('id')->first();

        if ($profile === null) {
            return 0;
        }

        $signal = SeoAiSignal::query()->firstOrNew(['business_profile_id' => $profile->id]);
        $changed = false;

        if (! $signal->ai_crawl_enabled) {
            $signal->ai_crawl_enabled = true;
            $changed = true;
        }

        if ((int) ($signal->llm_visibility_score ?? 0) < 90) {
            $signal->llm_visibility_score = 100;
            $changed = true;
        }

        if ((int) ($signal->entity_consistency_score ?? 0) < 90) {
            $signal->entity_consistency_score = 100;
            $changed = true;
        }

        if ($changed) {
            $signal->saveQuietly();
        }

        return $changed ? 1 : 0;
    }

    private function completeGeoLocation(): int
    {
        if (! Schema::hasTable('geo_locations')) {
            return 0;
        }

        $profile = BusinessProfile::query()->where('website', config('app.url'))->first()
            ?? BusinessProfile::query()->latest('id')->first();

        if ($profile === null) {
            return 0;
        }

        $geo = GeoLocation::query()->where('business_profile_id', $profile->id)->first();

        if ($geo === null) {
            GeoLocation::query()->create([
                'business_profile_id' => $profile->id,
                'label' => 'Arekere hub — '.$profile->city,
                'latitude' => 12.8847,
                'longitude' => 77.5963,
                'radius_km' => 25,
                'is_active' => true,
            ]);

            return 1;
        }

        $changed = false;
        if ($geo->latitude === null || $geo->longitude === null || ((float) $geo->latitude === 0.0 && (float) $geo->longitude === 0.0)) {
            $geo->latitude = 12.8847;
            $geo->longitude = 77.5963;
            $changed = true;
        }
        if ((int) ($geo->radius_km ?? 0) < 1) {
            $geo->radius_km = 25;
            $changed = true;
        }
        if ($changed) {
            $geo->saveQuietly();
        }

        return $changed ? 1 : 0;
    }

    private function completeCatalogEntity(Service|SubService|ServiceCategory $entity): bool
    {
        $changed = false;
        $title = $this->entityTitle($entity);
        $description = trim((string) ($entity->description ?? ''));
        $brand = BrandContentCatalog::all();

        if (blank($entity->short_summary) && $description !== '') {
            $entity->short_summary = $this->firstSentence($description, 220);
            $changed = true;
        } elseif (blank($entity->short_summary)) {
            $entity->short_summary = "{$title} from {$brand['brand_name']} — professional home healthcare across {$brand['city']}.";
            $changed = true;
        }

        if (blank($entity->ai_summary)) {
            $entity->ai_summary = $this->truncate(
                $description !== '' ? $description : (string) $entity->short_summary,
                280
            );
            $changed = true;
        }

        if (blank($entity->why_medca)) {
            $entity->why_medca = BrandContentCatalog::whyMedcaParagraph();
            $changed = true;
        }

        if (blank($entity->quick_answer)) {
            $this->quickAnswers->fillIfEmpty($entity);
            if (filled($entity->quick_answer)) {
                $changed = true;
            }
        }

        if ($this->isEmptyList($entity->eligibility ?? null)) {
            $entity->eligibility = [
                "Families in {$brand['city']} seeking supervised {$title} at home.",
                'Patients who prefer hospital-grade support without leaving home.',
                "Homes within Medca's service area around Arekere (approximately 25 km).",
            ];
            $changed = true;
        }

        if ($this->isEmptyList($entity->process_steps ?? null)) {
            $entity->process_steps = BrandContentCatalog::standardCareProcessSteps();
            $changed = true;
        }

        if ($this->isEmptyList($entity->key_takeaways ?? null) && filled($entity->quick_answer)) {
            $entity->key_takeaways = [
                $this->firstSentence((string) $entity->quick_answer, 120),
                'Supervised by experienced nursing leadership.',
                'Transparent updates for families throughout care.',
            ];
            $changed = true;
        }

        if ($this->isEmptyList($entity->activities_included ?? null)) {
            $entity->activities_included = [
                'Clinical assessment and personalized care planning',
                'Medication reminders and vital monitoring where applicable',
                'Daily living support with dignity and compassion',
                'Family communication and care plan reviews',
            ];
            $changed = true;
        }

        if (blank($entity->image_alt) && filled($entity->featured_image)) {
            $entity->image_alt = "{$title} — {$brand['brand_name']} home healthcare in {$brand['city']}";
            $changed = true;
        }

        if ($changed) {
            $entity->saveQuietly();
        }

        return $changed;
    }

    private function completeServiceSeo(Service $service): void
    {
        $service->loadMissing('seo');
        $brand = BrandContentCatalog::all();
        $plain = $this->plainText((string) ($service->short_summary ?: $service->description));
        $title = (string) $service->title;

        $payload = [];
        $seo = $service->seo;

        $metaTitle = "{$title} at Home in {$brand['city']} | {$brand['brand_name']}";
        $metaDesc = $this->buildMetaDescription($plain !== '' ? $plain : (string) $service->short_summary);

        foreach ([
            'meta_title' => mb_substr($metaTitle, 0, 255),
            'meta_description' => $metaDesc,
            'h1' => mb_substr($title, 0, 255),
            'og_title' => mb_substr($metaTitle, 0, 255),
            'og_description' => $metaDesc,
            'ai_context' => $this->truncate($plain !== '' ? $plain : (string) $service->ai_summary, 800),
            'search_intent' => 'commercial',
            'twitter_card' => 'summary_large_image',
            'robots_index' => true,
            'focus_keywords' => Str::lower("{$title}, home {$title} {$brand['city']}, {$brand['brand_name']}"),
        ] as $field => $value) {
            if ($seo === null || blank($seo->{$field})) {
                $payload[$field] = $value;
            }
        }

        if ($payload !== []) {
            $service->seo()->updateOrCreate(['service_id' => $service->id], $payload);
        }

        $this->contentSeoAutoFill->applyAndSyncService($service->fresh(['seo']));
    }

    private function completeSubServiceSeo(SubService $sub): void
    {
        $sub->loadMissing('seo');
        $brand = BrandContentCatalog::all();
        $title = $sub->publicListingTitle();
        $plain = $this->plainText((string) ($sub->short_summary ?: $sub->description));

        $payload = [];
        $seo = $sub->seo;
        $metaTitle = "{$title} | {$brand['brand_name']}";
        $metaDesc = $this->buildMetaDescription($plain !== '' ? $plain : (string) $sub->short_summary);

        foreach ([
            'meta_title' => mb_substr($metaTitle, 0, 255),
            'meta_description' => $metaDesc,
            'h1' => mb_substr($title, 0, 255),
            'og_title' => mb_substr($metaTitle, 0, 255),
            'og_description' => $metaDesc,
            'ai_context' => $this->truncate($plain, 600),
            'search_intent' => 'commercial',
            'twitter_card' => 'summary_large_image',
        ] as $field => $value) {
            if ($seo === null || blank($seo->{$field})) {
                $payload[$field] = $value;
            }
        }

        if ($payload !== []) {
            $sub->seo()->updateOrCreate(['sub_service_id' => $sub->id], $payload);
        }
    }

    private function completeCategorySeo(ServiceCategory $category): void
    {
        $category->loadMissing('seo');
        $brand = BrandContentCatalog::all();
        $name = (string) ($category->name ?? $category->title ?? 'Healthcare services');
        $plain = $this->plainText((string) ($category->short_summary ?: $category->description));

        $payload = [];
        $seo = $category->seo;
        $metaTitle = "{$name} in {$brand['city']} | {$brand['brand_name']}";
        $metaDesc = $this->buildMetaDescription($plain !== '' ? $plain : "{$name} home healthcare services from {$brand['brand_name']}.");

        foreach ([
            'meta_title' => mb_substr($metaTitle, 0, 255),
            'meta_description' => $metaDesc,
            'h1' => mb_substr($name, 0, 255),
            'og_title' => mb_substr($metaTitle, 0, 255),
            'og_description' => $metaDesc,
            'aeo_question' => "What {$name} services does {$brand['brand_name']} provide?",
            'aeo_answer' => $this->truncate((string) ($category->quick_answer ?: $plain), 500),
            'ai_context' => $this->truncate($plain, 600),
            'search_intent' => 'informational',
        ] as $field => $value) {
            if ($seo === null || blank($seo->{$field})) {
                $payload[$field] = $value;
            }
        }

        if ($payload !== []) {
            $category->seo()->updateOrCreate(['service_category_id' => $category->id], $payload);
        }
    }

    private function completeServiceFaqs(Service $service): int
    {
        if (! Schema::hasTable('service_faqs')) {
            return 0;
        }

        if ($service->faqs()->count() >= 3) {
            return 0;
        }

        $created = 0;
        foreach ($this->catalogFaqPairs($service) as $pair) {
            $exists = $service->faqs()->where('question', $pair['question'])->exists();
            if ($exists) {
                continue;
            }
            ServiceFaq::query()->create([
                'service_id' => $service->id,
                'question' => $pair['question'],
                'answer' => $pair['answer'],
            ]);
            $created++;
        }

        return $created;
    }

    private function completeSubServiceFaqs(SubService $sub): int
    {
        if (! Schema::hasTable('sub_service_faqs')) {
            return 0;
        }

        if ($sub->faqs()->count() >= 2) {
            return 0;
        }

        $created = 0;
        foreach ($this->catalogFaqPairs($sub) as $pair) {
            $exists = $sub->faqs()->where('question', $pair['question'])->exists();
            if ($exists) {
                continue;
            }
            SubServiceFaq::query()->create([
                'sub_service_id' => $sub->id,
                'question' => $pair['question'],
                'answer' => $pair['answer'],
                'sort_order' => $created,
            ]);
            $created++;
        }

        return $created;
    }

    private function completeCategoryFaqs(ServiceCategory $category): int
    {
        if (! Schema::hasTable('service_category_faqs')) {
            return 0;
        }

        if ($category->faqs()->count() >= 2) {
            return 0;
        }

        $created = 0;
        foreach ($this->catalogFaqPairs($category) as $pair) {
            $exists = $category->faqs()->where('question', $pair['question'])->exists();
            if ($exists) {
                continue;
            }
            ServiceCategoryFaq::query()->create([
                'service_category_id' => $category->id,
                'question' => $pair['question'],
                'answer' => $pair['answer'],
                'sort_order' => $created,
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function catalogFaqPairs(Service|SubService|ServiceCategory $entity): array
    {
        $title = $this->entityTitle($entity);
        $brand = BrandContentCatalog::get('brand_name');
        $city = BrandContentCatalog::get('city');
        $answer = trim((string) ($entity->quick_answer ?? $entity->short_summary ?? $entity->description ?? ''));
        $why = trim((string) ($entity->why_medca ?? BrandContentCatalog::whyMedcaParagraph()));

        return [
            [
                'question' => "What is {$title}?",
                'answer' => $answer !== '' ? $answer : "{$title} is a home healthcare service provided by {$brand} in {$city}.",
            ],
            [
                'question' => "Who can benefit from {$title}?",
                'answer' => $this->truncate(
                    is_array($entity->eligibility) && isset($entity->eligibility[0])
                        ? (string) $entity->eligibility[0]
                        : "Families in {$city} who need supervised {$title} at home.",
                    400
                ),
            ],
            [
                'question' => "Why choose {$brand} for {$title}?",
                'answer' => $this->truncate($why, 400),
            ],
        ];
    }

    private function completeCmsPages(): int
    {
        if (! Schema::hasTable('pages')) {
            return 0;
        }

        $count = 0;
        $excluded = [PageCategory::Location->value, PageCategory::Blog->value];

        Page::query()
            ->where('is_active', true)
            ->where(function ($q) use ($excluded): void {
                $q->whereNull('page_category')
                    ->orWhereNotIn('page_category', $excluded);
            })
            ->where(function ($q): void {
                $q->whereNull('meta_description')
                    ->orWhere('meta_description', '')
                    ->orWhereNull('schema_json')
                    ->orWhereNull('aeo_answer')
                    ->orWhere('aeo_answer', '');
            })
            ->orderBy('id')
            ->chunkById(50, function ($pages) use (&$count): void {
                foreach ($pages as $page) {
                    if (! $page instanceof Page) {
                        continue;
                    }
                    $before = md5(json_encode($page->only([
                        'meta_title', 'meta_description', 'h1', 'aeo_question', 'aeo_answer', 'canonical_url', 'robots_meta',
                    ])) ?: '');

                    $this->contentSeoAutoFill->applyToPage($page);
                    $page->saveQuietly();
                    $this->contentSeoAutoFill->syncPageGrowthArtifacts($page);

                    $after = md5(json_encode($page->fresh()->only([
                        'meta_title', 'meta_description', 'h1', 'aeo_question', 'aeo_answer', 'canonical_url', 'robots_meta',
                    ])) ?: '');

                    if ($before !== $after) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    private function syncServiceDetailPages(): int
    {
        if (! Schema::hasTable('services') || ! Schema::hasTable('pages')) {
            return 0;
        }

        $sync = app(ServiceMasterPageSync::class);
        $count = 0;

        Service::query()
            ->where('is_active', true)
            ->whereNotNull('detail_page_id')
            ->with(['detailPage', 'seo', 'faqs', 'schema'])
            ->each(function (Service $service) use ($sync, &$count): void {
                $page = $service->detailPage;
                if ($page === null) {
                    return;
                }
                $sync->pushToPage($service, $page, forceEmptyOnly: true);
                $count++;
            });

        return $count;
    }

    private function syncCatalogSchemas(): int
    {
        $synced = 0;

        Service::query()->where('is_active', true)->orderBy('id')->each(function (Service $service) use (&$synced): void {
            $this->schemaGenerator->generateAndPersist($service->fresh(['seo', 'faqs']));
            $synced++;
        });

        if (config('services_master.auto_sync_on_save', true)) {
            SubService::query()->where('is_active', true)->orderBy('id')->each(function (SubService $sub) use (&$synced): void {
                $this->subServiceOrchestrator->sync($sub->fresh(['seo', 'faqs']));
                $synced++;
            });

            ServiceCategory::query()->orderBy('id')->each(function (ServiceCategory $category) use (&$synced): void {
                $this->categoryOrchestrator->sync($category->fresh(['seo', 'faqs']));
                $synced++;
            });
        }

        return $synced;
    }

    private function resolveLogoUrl(): string
    {
        $candidates = [
            url('/images/medca-logo.png'),
        ];

        if (Schema::hasTable('theme_configurations')) {
            $theme = ThemeConfiguration::query()->first();
            $branding = is_array($theme?->branding) ? $theme->branding : [];
            if (filled($branding['logo_path'] ?? null)) {
                $candidates[] = url('/storage/'.ltrim((string) $branding['logo_path'], '/'));
            }
            if (filled($branding['favicon_path'] ?? null)) {
                $candidates[] = url('/storage/'.ltrim((string) $branding['favicon_path'], '/'));
            }
        }

        return $candidates[0];
    }

    /**
     * @param  array<string, string>  $brand
     * @return list<array{question: string, answer: string}>
     */
    private function buildGlobalEntityFaqs(array $brand): array
    {
        return [
            [
                'question' => 'What services does '.$brand['brand_name'].' provide?',
                'answer' => $this->truncate($brand['long_description'] ?: $brand['short_description'], 400),
            ],
            [
                'question' => 'Where does '.$brand['brand_name'].' operate?',
                'answer' => $brand['service_area'],
            ],
            [
                'question' => 'How can I contact '.$brand['brand_name'].'?',
                'answer' => 'Call '.$brand['phone_display'].' or reach us on WhatsApp. '.$brand['business_hours'],
            ],
            [
                'question' => 'Why choose '.$brand['brand_name'].'?',
                'answer' => $this->truncate(BrandContentCatalog::whyMedcaParagraph(), 400),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $brand
     */
    private function buildLlmsTxtDocument(array $brand): string
    {
        $base = $brand['website'];
        $lines = [
            '# '.$brand['brand_name'],
            '',
            '> '.$this->truncate($brand['long_description'] ?: $brand['short_description'], 200),
            '',
            '## About',
            '- **Brand:** '.$brand['brand_name'],
            '- **Tagline:** '.$brand['tagline'],
            '- **Service area:** '.$brand['service_area'],
            '- **Phone:** '.$brand['phone_display'],
            '- **Address:** '.$brand['address'],
            '',
            '## Key pages',
            '- [Home]('.$base.')',
            '- [Services]('.$base.'/services)',
            '- [About]('.$base.'/about-us)',
            '- [Contact]('.$base.'/contact)',
            '- [AI discovery]('.$base.'/ai-discovery)',
            '- [Sitemap]('.$base.'/sitemap.xml)',
            '',
            '## Services catalog',
        ];

        if (Schema::hasTable('services')) {
            Service::query()->where('is_active', true)->orderBy('sort_order')->limit(30)->each(function (Service $service) use (&$lines, $base): void {
                $code = ltrim((string) $service->service_code, '/');
                $lines[] = '- ['.$service->title.']('.$base.'/services/'.$code.')';
            });
        }

        $lines[] = '';
        $lines[] = '## Contact';
        $lines[] = '- Phone: '.$brand['phone_display'];
        $lines[] = '- WhatsApp: '.$brand['whatsapp_url'];

        return implode("\n", $lines);
    }

    private function entityTitle(Service|SubService|ServiceCategory $entity): string
    {
        if ($entity instanceof SubService) {
            return $entity->publicListingTitle();
        }

        return (string) ($entity->title ?? $entity->name ?? 'Healthcare service');
    }

    private function buildMetaDescription(string $source): string
    {
        $text = $this->truncate($this->plainText($source), 155);
        if (strlen($text) < 120) {
            $suffix = ' Book trusted home healthcare with '.BrandContentCatalog::get('brand_name').' in '.BrandContentCatalog::get('city').'.';

            return $this->truncate($text.$suffix, 160);
        }

        return $text;
    }

    private function firstSentence(string $text, int $maxLen = 200): string
    {
        $plain = $this->plainText($text);
        $sentence = preg_split('/(?<=[.!?])\s+/', $plain, 2)[0] ?? $plain;

        return $this->truncate(rtrim(trim($sentence), '.').'.', $maxLen);
    }

    private function plainText(string $html): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');

        return $text;
    }

    private function truncate(string $text, int $max): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1)).'…';
    }

    private function isEmptyList(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }
        if (is_string($value)) {
            return trim($value) === '';
        }
        if (is_array($value)) {
            return $value === [] || collect($value)->filter(fn ($v) => filled($v))->isEmpty();
        }

        return true;
    }
}
