<?php

namespace App\Services\Operations;

use App\Models\ServiceCategory;
use App\Models\ServiceCategoryFaq;
use App\Models\ServiceCategorySchema;
use App\Models\ServiceCategorySeo;
use App\Models\SubService;
use App\Models\SubServiceFaq;
use App\Models\SubServiceSchema;
use App\Models\SubServiceSeo;
use App\Services\Media\CatalogMediaAttacher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CatalogMasterPersister
{
    public function __construct(
        private readonly CatalogMediaAttacher $mediaAttacher,
        private readonly CatalogOptimizationScorer $optimizationScorer,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function persistCategory(ServiceCategory $category, Request $request, array $data): ServiceCategory
    {
        $category->fill(array_merge(
            $this->categoryCoreAttributes($data, $request),
            $this->contentAttributesFromValidated($data),
        ));

        $this->mediaAttacher->syncFromRequest($request, $category, 'categories');
        $category->save();

        $this->syncCategorySeo($category, is_array($data['seo'] ?? null) ? $data['seo'] : []);
        $this->syncCategoryFaqs($category, is_array($data['faqs'] ?? null) ? $data['faqs'] : []);
        $this->syncCategorySchema($category, $data['schema_type'] ?? null, $data['schema_json'] ?? null);

        $category = $category->fresh(['seo', 'faqs', 'schema']);
        $this->optimizationScorer->scoreAndPersist($category);
        app(CategoryMasterOrchestrator::class)->sync($category);

        return $category->fresh(['seo', 'faqs', 'schema']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function persistSubService(SubService $subService, Request $request, array $data): SubService
    {
        $subService->fill(array_merge([
            'sub_service_code' => $data['sub_service_code'],
            'title' => $data['title'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'is_featured' => $request->boolean('is_featured', false),
            'is_top_rated' => $request->boolean('is_top_rated', false),
            'show_on_homepage' => $request->boolean('show_on_homepage', false),
            'show_on_about' => $request->boolean('show_on_about', false),
            'show_on_contact' => $request->boolean('show_on_contact', false),
            'publish_status' => $data['publish_status'],
            'visibility' => $data['visibility'],
            'page_id' => filled($data['page_id'] ?? null) ? (int) $data['page_id'] : null,
        ], $this->contentAttributesFromValidated($data)));

        $this->mediaAttacher->syncFromRequest($request, $subService, 'sub-services');
        $subService->save();

        $this->syncSubServiceSeo($subService, is_array($data['seo'] ?? null) ? $data['seo'] : []);
        $this->syncSubServiceFaqs($subService, is_array($data['faqs'] ?? null) ? $data['faqs'] : []);
        $this->syncSubServiceSchema($subService, $data['schema_type'] ?? null, $data['schema_json'] ?? null);

        $subService = $subService->fresh(['seo', 'faqs', 'schema', 'service']);
        $this->optimizationScorer->scoreAndPersist($subService);
        app(SubServiceMasterOrchestrator::class)->sync($subService);

        return $subService->fresh(['seo', 'faqs', 'schema', 'service']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function contentAttributesFromValidated(array $data): array
    {
        return [
            'short_summary' => $data['short_summary'] ?? null,
            'description' => $data['description'] ?? null,
            'key_benefits' => $this->nullableLinesArray($data['key_benefits'] ?? null),
            'eligibility' => $this->nullableLinesArray($data['eligibility'] ?? null),
            'process_steps' => $this->nullableLinesArray($data['process_steps'] ?? null),
            'ai_summary' => $data['ai_summary'] ?? null,
            'procedures' => $data['procedures'] ?? null,
            'specialized_care' => $data['specialized_care'] ?? null,
            'shifts' => $data['shifts'] ?? null,
            'price_range' => $data['price_range'] ?? null,
            'image_alt' => $data['image_alt'] ?? ($data['featured_image_meta']['alt'] ?? null),
            'featured_image_meta' => is_array($data['featured_image_meta'] ?? null) ? $data['featured_image_meta'] : null,
            'gallery_meta' => is_array($data['gallery_meta'] ?? null) ? $data['gallery_meta'] : null,
            'trust_signals' => is_array($data['trust_signals'] ?? null) ? $data['trust_signals'] : null,
            'target_keywords' => $this->nullableKeywordArray($data['target_keywords'] ?? null),
            'ai_keywords' => $this->nullableKeywordArray($data['ai_keywords'] ?? null),
            'custom_fields' => is_array($data['custom_fields'] ?? null) ? $data['custom_fields'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function categoryCoreAttributes(array $data, Request $request): array
    {
        return [
            'name' => trim((string) ($data['name'] ?? '')),
            'code' => ServiceCategory::normalizeCode((string) ($data['code'] ?? '')),
            'slug' => filled($data['slug'] ?? null) ? ServiceCategory::normalizeCode((string) $data['slug']) : null,
            'parent_id' => filled($data['parent_id'] ?? null) ? (int) $data['parent_id'] : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
            'is_featured' => $request->boolean('is_featured', false),
            'visibility' => (string) ($data['visibility'] ?? 'public'),
            'show_on_homepage' => $request->boolean('show_on_homepage', false),
            'show_on_about' => $request->boolean('show_on_about', false),
            'show_on_contact' => $request->boolean('show_on_contact', false),
            'publish_status' => $data['publish_status'] ?? 'published',
            'page_id' => filled($data['page_id'] ?? null) ? (int) $data['page_id'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $seoInput
     */
    private function syncCategorySeo(ServiceCategory $category, array $seoInput): void
    {
        $payload = $this->buildSeoPayload($seoInput, $category->publicUrl());

        ServiceCategorySeo::query()->updateOrCreate(
            ['service_category_id' => $category->id],
            $payload
        );
    }

    /**
     * @param  array<string, mixed>  $seoInput
     */
    private function syncSubServiceSeo(SubService $subService, array $seoInput): void
    {
        $payload = $this->buildSeoPayload($seoInput, $subService->publicUrl());

        SubServiceSeo::query()->updateOrCreate(
            ['sub_service_id' => $subService->id],
            $payload
        );
    }

    /**
     * @param  array<string, mixed>  $seoInput
     * @return array<string, mixed>
     */
    private function buildSeoPayload(array $seoInput, string $defaultCanonical): array
    {
        $focus = array_values(array_filter($seoInput['focus_keywords'] ?? [], fn ($v) => is_string($v) && $v !== ''));
        $h2 = array_values(array_filter($seoInput['h2'] ?? [], fn ($v) => is_string($v) && $v !== ''));
        $h3 = array_values(array_filter($seoInput['h3'] ?? [], fn ($v) => is_string($v) && $v !== ''));
        $secondary = array_values(array_filter($seoInput['secondary_keywords'] ?? [], fn ($v) => is_string($v) && $v !== ''));

        return array_filter([
            'meta_title' => $seoInput['meta_title'] ?? null,
            'meta_description' => $seoInput['meta_description'] ?? null,
            'h1' => $seoInput['h1'] ?? null,
            'focus_keywords' => $focus !== [] ? $focus : null,
            'secondary_keywords' => $secondary !== [] ? $secondary : null,
            'h2' => $h2 !== [] ? $h2 : null,
            'h3' => $h3 !== [] ? $h3 : null,
            'ai_context' => $seoInput['ai_context'] ?? null,
            'search_intent' => $seoInput['search_intent'] ?? null,
            'canonical_url' => $seoInput['canonical_url'] ?? $defaultCanonical,
            'robots_index' => array_key_exists('robots_index', $seoInput) ? (bool) $seoInput['robots_index'] : true,
            'og_title' => $seoInput['og_title'] ?? null,
            'og_description' => $seoInput['og_description'] ?? null,
            'og_image' => $seoInput['og_image'] ?? null,
            'twitter_card' => $seoInput['twitter_card'] ?? 'summary_large_image',
            'entity_tags' => $this->nullableKeywordArray($seoInput['entity_tags'] ?? null),
            'geo_entities' => $this->nullableKeywordArray($seoInput['geo_entities'] ?? null),
            'aeo_question' => $seoInput['aeo_question'] ?? null,
            'aeo_answer' => $seoInput['aeo_answer'] ?? null,
        ], static fn ($v) => $v !== null && $v !== '');
    }

    /**
     * @param  list<array{question?: string, answer?: string}>  $rows
     */
    private function syncCategoryFaqs(ServiceCategory $category, array $rows): void
    {
        $this->replaceFaqs($category, ServiceCategoryFaq::class, 'service_category_id', $rows);
    }

    /**
     * @param  list<array{question?: string, answer?: string}>  $rows
     */
    private function syncSubServiceFaqs(SubService $subService, array $rows): void
    {
        $this->replaceFaqs($subService, SubServiceFaq::class, 'sub_service_id', $rows);
    }

    /**
     * @param  class-string  $faqClass
     * @param  list<array{question?: string, answer?: string}>  $rows
     */
    private function replaceFaqs(Model $owner, string $faqClass, string $foreignKey, array $rows): void
    {
        $owner->faqs()->delete();

        foreach ($rows as $i => $row) {
            if (! is_array($row)) {
                continue;
            }

            $q = isset($row['question']) ? trim((string) $row['question']) : '';
            $a = isset($row['answer']) ? trim((string) $row['answer']) : '';
            if ($q === '' && $a === '') {
                continue;
            }

            $faqClass::query()->create([
                $foreignKey => $owner->id,
                'question' => $q !== '' ? $q : __('Question'),
                'answer' => $a,
                'sort_order' => $i,
            ]);
        }
    }

    private function syncCategorySchema(ServiceCategory $category, ?string $schemaType, ?string $schemaJsonRaw): void
    {
        $this->syncSchemaRecord($category, ServiceCategorySchema::class, 'service_category_id', $schemaType ?: 'CollectionPage', $schemaJsonRaw);
    }

    private function syncSubServiceSchema(SubService $subService, ?string $schemaType, ?string $schemaJsonRaw): void
    {
        $this->syncSchemaRecord($subService, SubServiceSchema::class, 'sub_service_id', $schemaType ?: 'Service', $schemaJsonRaw);
    }

    /**
     * @param  class-string  $schemaClass
     */
    private function syncSchemaRecord(Model $owner, string $schemaClass, string $foreignKey, ?string $schemaType, ?string $schemaJsonRaw): void
    {
        $decoded = null;
        if (is_string($schemaJsonRaw) && $schemaJsonRaw !== '') {
            $decoded = json_decode($schemaJsonRaw, true);
        }

        if (! is_array($decoded)) {
            $decoded = [];
        }

        if (($schemaType === null || $schemaType === '') && $decoded === []) {
            $owner->schema()?->delete();

            return;
        }

        $schemaClass::query()->updateOrCreate(
            [$foreignKey => $owner->id],
            [
                'schema_type' => $schemaType ?: 'Thing',
                'schema_json' => $decoded,
            ]
        );
    }

    /**
     * @param  mixed  $value
     * @return list<string>|null
     */
    private function nullableLinesArray(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $items = array_values(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            $value
        ), static fn (string $item): bool => $item !== ''));

        return $items === [] ? null : $items;
    }

    /**
     * @param  mixed  $value
     * @return list<string>|null
     */
    private function nullableKeywordArray(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $items = array_values(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            $value
        ), static fn (string $item): bool => $item !== ''));

        return $items === [] ? null : $items;
    }
}
