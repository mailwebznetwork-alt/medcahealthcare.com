<?php

namespace App\Services\Growth;

use App\Models\BusinessProfile;
use App\Models\PageElement;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\SeoAiSignal;
use App\Models\SeoEntity;
use App\Models\SeoTechnical;
use App\Services\MasterSpec\QuickAnswerGenerator;
use App\Services\Public\PublicDisplayNameResolver;
use Illuminate\Support\Facades\Schema;

class AeoService
{
    public function saveSignals(array $data): SeoAiSignal
    {
        $profile = BusinessProfile::query()->first();

        if (! $profile instanceof BusinessProfile) {
            $profile = BusinessProfile::query()->create([
                'name' => config('medca.brand_name', 'Karnataka Diagnostic Centre'),
                'email' => config('mail.from.address'),
                'website' => config('app.url'),
            ]);
        }

        return SeoAiSignal::query()->updateOrCreate(
            ['business_profile_id' => $profile->id],
            [
                'ai_crawl_enabled' => (bool) ($data['ai_crawl_enabled'] ?? false),
                'llm_visibility_score' => (int) ($data['llm_visibility_score'] ?? 0),
                'entity_consistency_score' => (int) ($data['entity_consistency_score'] ?? 0),
            ]
        );
    }

    public function generateLlmTxt(): string
    {
        if (Schema::hasTable('seo_technical') && Schema::hasTable('business_profiles')) {
            $profile = BusinessProfile::query()->where('website', config('app.url'))->first()
                ?? BusinessProfile::query()->latest('id')->first();

            if ($profile instanceof BusinessProfile) {
                $technical = SeoTechnical::query()->where('business_profile_id', $profile->id)->first();
                $custom = trim((string) ($technical?->llm_txt ?? ''));
                if ($custom !== '') {
                    return $custom;
                }
            }
        }

        return implode("\n", [
            'User-agent: GPTBot',
            'Allow: /',
            '',
            'User-agent: Google-Extended',
            'Allow: /',
            '',
            'User-agent: ClaudeBot',
            'Allow: /',
        ]);
    }

    /**
     * llms.txt — machine-readable site guide for AI crawlers (llmstxt.org-style).
     */
    public function generateLlmsTxt(): string
    {
        if (Schema::hasTable('seo_technical') && Schema::hasTable('business_profiles')) {
            $profile = BusinessProfile::query()->where('website', config('app.url'))->first()
                ?? BusinessProfile::query()->latest('id')->first();

            if ($profile instanceof BusinessProfile) {
                $technical = SeoTechnical::query()->where('business_profile_id', $profile->id)->first();
                $custom = trim((string) ($technical?->llm_txt ?? ''));
                if ($custom !== '') {
                    return $custom;
                }
            }
        }

        $baseUrl = rtrim((string) config('app.url'), '/');
        $brand = (string) config('medca.brand_name', 'Karnataka Diagnostic Centre');
        $profile = Schema::hasTable('business_profiles')
            ? (BusinessProfile::query()->latest('id')->first())
            : null;
        $entity = Schema::hasTable('seo_entities') ? SeoEntity::query()->latest('id')->first() : null;

        $summary = trim((string) ($entity?->meta_description ?? ''));
        if ($summary === '') {
            $summary = __('Reliable medical laboratory and diagnostic services in Karnataka.');
        }

        $lines = [
            '# '.$brand,
            '',
            '> '.$summary,
            '',
            '## Contact',
            '- Website: '.$baseUrl,
        ];

        if ($profile !== null) {
            if (filled($profile->phone_e164 ?? $profile->phone)) {
                $lines[] = '- Phone: '.($profile->phone_e164 ?? $profile->phone);
            }
            if (filled($profile->email)) {
                $lines[] = '- Email: '.$profile->email;
            }
            if (filled($profile->city)) {
                $lines[] = '- Service area: '.$profile->city.(filled($profile->region) ? ', '.$profile->region : '');
            }
        }

        $lines[] = '';
        $lines[] = '## Discovery';
        $lines[] = '- [Sitemap]('.$baseUrl.'/sitemap.xml)';
        $lines[] = '- [HTML sitemap]('.$baseUrl.'/sitemap)';
        $lines[] = '- [AI discovery JSON]('.$baseUrl.'/ai-discovery)';
        $lines[] = '- [Bot policy]('.$baseUrl.'/llm.txt)';

        if (Schema::hasTable('services')) {
            $resolver = app(PublicDisplayNameResolver::class);
            $quickAnswers = app(QuickAnswerGenerator::class);

            $services = Service::query()
                ->with('seo')
                ->where('is_active', true)
                ->orderByDesc('is_featured')
                ->orderBy('service_code')
                ->limit(25)
                ->get();

            if ($services->isNotEmpty()) {
                $lines[] = '';
                $lines[] = '## Services';

                foreach ($services as $service) {
                    $title = $service->publicListingTitle();
                    $url = $service->publicUrl();
                    $snippet = filled($service->quick_answer)
                        ? $service->quick_answer
                        : $quickAnswers->generateForService($service);
                    $lines[] = '- ['.$title.']('.$url.')'.($snippet ? ': '.$snippet : '');
                }
            }
        }

        $lines[] = '';
        $lines[] = '## Crawler policy';
        $lines[] = '- GPTBot, Google-Extended, ClaudeBot: Allow /';

        return implode("\n", $lines);
    }

    /**
     * @return array{
     *     services: array<int, array<string, mixed>>,
     *     pages: array<int, array<string, mixed>>,
     *     locations: array<int, array<string, mixed>>,
     *     business: ?array<string, mixed>,
     *     contact: ?array<string, mixed>
     * }
     */
    public function generateDiscoveryData(): array
    {
        $resolver = app(PublicDisplayNameResolver::class);
        $quickAnswers = app(QuickAnswerGenerator::class);

        $services = Schema::hasTable('services')
            ? Service::query()
                ->with('seo')
                ->where('is_active', true)
                ->orderBy('service_code')
                ->get()
                ->map(function (Service $service) use ($resolver, $quickAnswers): array {
                    $meta = $resolver->documentMeta($service);

                    return [
                        'code' => $service->service_code,
                        'url' => $service->publicUrl(),
                        'title' => $service->publicListingTitle(),
                        'meta_title' => $meta['title'] ?? $service->seo?->meta_title,
                        'meta_description' => $meta['description'] ?? $service->seo?->meta_description,
                        'quick_answer' => filled($service->quick_answer)
                            ? $service->quick_answer
                            : $quickAnswers->generateForService($service),
                        'ai_summary' => $service->ai_summary,
                        'search_intent' => $service->seo?->search_intent,
                        'medical_review_status' => $service->medical_review_status?->value ?? $service->medical_review_status,
                        'verification_status' => $service->verification_status?->value ?? $service->verification_status,
                        'llm_score' => (int) ($service->seo?->ai_discovery_score ?? 0),
                    ];
                })
                ->values()
                ->all()
            : [];

        $locations = Schema::hasTable('pin_codes')
            ? PinCode::query()
                ->with('bangaloreZone')
                ->orderBy('pincode')
                ->get()
                ->map(fn (PinCode $pincode): array => [
                    'pincode' => $pincode->pincode,
                    'area_name' => $pincode->area_name,
                    'slug' => $pincode->slug,
                    'bangalore_zone' => $pincode->bangaloreZone?->name,
                    'landing_page' => $pincode->landing_page,
                    'serviceable' => (bool) $pincode->is_serviceable,
                    'priority' => $pincode->priority,
                ])
                ->values()
                ->all()
            : [];

        $businessEntity = Schema::hasTable('seo_entities')
            ? SeoEntity::query()->latest('id')->first()
            : null;

        $contactProfile = Schema::hasTable('business_profiles')
            ? BusinessProfile::query()->latest('id')->first()
            : null;

        return [
            'services' => $services,
            'pages' => $services,
            'locations' => $locations,
            'business' => $businessEntity?->only([
                'organization_name',
                'logo',
                'same_as',
                'meta_title',
                'meta_description',
                'og_image_url',
                'custom_json_ld',
            ]),
            'contact' => $contactProfile === null ? null : array_filter([
                'name' => $contactProfile->name,
                'email' => $contactProfile->email,
                'phone' => $contactProfile->phone,
                'phone_e164' => $contactProfile->phone_e164,
                'country_code' => $contactProfile->country_code,
                'website' => $contactProfile->website,
                'address' => $contactProfile->address,
                'street_address' => $contactProfile->street_address,
                'city' => $contactProfile->city,
                'region' => $contactProfile->region,
                'postal_code' => $contactProfile->postal_code,
            ], fn (mixed $v): bool => $v !== null && $v !== ''),
        ];
    }

    public function calculateLlmScore(string $slug): int
    {
        if (! Schema::hasTable('page_elements')) {
            return 0;
        }

        $elements = PageElement::query()
            ->where('page_slug', $slug)
            ->get(['section', 'key', 'value', 'type']);

        if ($elements->isEmpty()) {
            return 0;
        }

        $presenceScore = min(40, $elements->count() * 8);
        $structuredScore = min(30, $elements->whereIn('type', ['json', 'schema', 'list'])->count() * 10);
        $completenessScore = min(
            30,
            $elements
                ->filter(fn (PageElement $element): bool => filled(trim((string) $element->value)))
                ->count() * 4
        );

        return min(100, $presenceScore + $structuredScore + $completenessScore);
    }
}
