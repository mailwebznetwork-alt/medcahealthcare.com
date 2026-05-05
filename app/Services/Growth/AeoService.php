<?php

namespace App\Services\Growth;

use App\Models\BusinessProfile;
use App\Models\PageElement;
use App\Models\PageSeo;
use App\Models\Pincode;
use App\Models\SeoAiSignal;
use App\Models\SeoEntity;
use Illuminate\Support\Facades\Schema;

class AeoService
{
    public function saveSignals(array $data): SeoAiSignal
    {
        $profile = BusinessProfile::query()->first();

        if (! $profile instanceof BusinessProfile) {
            $profile = BusinessProfile::query()->create([
                'name' => config('app.name'),
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
        $services = Schema::hasTable('page_seo')
            ? PageSeo::query()
                ->orderBy('page_slug')
                ->get()
                ->map(fn (PageSeo $pageSeo): array => [
                    'slug' => $pageSeo->page_slug,
                    'meta_title' => $pageSeo->meta_title,
                    'meta_description' => $pageSeo->meta_description,
                    'llm_score' => $this->calculateLlmScore($pageSeo->page_slug),
                ])
                ->values()
                ->all()
            : [];

        $locations = Schema::hasTable('pincodes')
            ? Pincode::query()
                ->orderBy('pincode')
                ->get()
                ->map(fn (Pincode $pincode): array => [
                    'pincode' => $pincode->pincode,
                    'landing_page' => $pincode->landing_page,
                    'serviceable' => (bool) $pincode->serviceable,
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
            ]),
            'contact' => $contactProfile?->only([
                'name',
                'email',
                'phone',
                'website',
                'address',
            ]),
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
