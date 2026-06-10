<?php

namespace App\Services\Import;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceFaq;
use App\Services\Governance\MasterDataProtection;
use App\Services\Governance\MasterDataAudit;
use App\Services\Governance\ServiceCreationGuard;
use App\Support\FakerContentGuard;

final class ServiceEntityImporter extends AbstractSpreadsheetImporter
{
    public function __construct(
        SpreadsheetReader $reader,
        ImportBatchRecorder $recorder,
        private readonly ImportServiceFieldMapper $fieldMapper,
        private readonly ServiceImportDefaults $defaults,
    ) {
        parent::__construct($reader, $recorder);
    }

    public function entityKey(): string
    {
        return 'services';
    }

    protected function requiredColumns(): array
    {
        return ['service_code', 'title'];
    }

    protected function optionalColumns(): array
    {
        return [
            'primary_category_code', 'category_codes', 'description', 'short_summary',
            'key_benefits', 'eligibility', 'process_steps', 'preparation', 'duration',
            'requirements', 'deliverables', 'trust_signals', 'procedures', 'specialized_care', 'shifts',
            'coverage_notes', 'emergency_coverage_notes', 'sort_order', 'is_active',
            'publish_status', 'visibility', 'meta_title', 'meta_description', 'focus_keywords',
            'secondary_keywords', 'canonical_url', 'robots_index', 'og_title', 'og_description',
            'og_image', 'twitter_title', 'twitter_description', 'twitter_image', 'breadcrumb_title',
            'h1', 'h2_lines', 'h3_lines', 'h4_lines', 'h5_lines', 'h6_lines', 'faq_pairs',
            'ai_summary', 'ai_recommendation_summary', 'target_keywords', 'ai_keywords',
            'voice_search_queries', 'conversational_queries', 'entity_references', 'search_intent',
            'ai_context', 'schema_type', 'schema_json_override', 'is_featured', 'is_top_rated',
            'show_on_homepage', 'show_on_about', 'show_on_contact', 'show_on_category_pages',
            'show_on_location_pages', 'display_priority', 'related_service_codes',
            'related_category_codes', 'related_sub_service_codes', 'related_location_pincode',
            'location_h1_template', 'location_h2_template', 'location_h3_template',
            'location_intro_template', 'location_description_template', 'location_faq_template',
            'location_cta_heading', 'location_cta_content', 'location_meta_title_template',
            'location_meta_description_template', 'featured_image_url', 'banner_image_url',
            'icon_url', 'gallery_image_urls', 'video_url', 'image_alt',
        ];
    }

    protected function previewRow(array $row, int $line): array
    {
        $code = trim((string) ($row['service_code'] ?? ''));
        if ($code === '') {
            return ['status' => 'invalid', 'detail' => __('Missing service code.'), 'key' => null];
        }

        $exists = Service::query()->where('service_code', $code)->exists();

        return [
            'status' => $exists ? 'update' : 'ready',
            'detail' => $exists ? __('Will update existing service.') : null,
            'key' => $code,
        ];
    }

    protected function importRow(array $row, int $line): array
    {
        $row = $this->defaults->mergeForService($row);

        $code = trim((string) ($row['service_code'] ?? ''));
        $title = trim((string) ($row['title'] ?? ''));
        if ($code === '' || $title === '') {
            return ['action' => 'failed', 'error' => __('Missing service_code or title.')];
        }

        $fakerGuard = app(FakerContentGuard::class);
        if ($fakerGuard->applies() && $fakerGuard->isCatalogFaker($title, $code, $row['description'] ?? $row['short_summary'] ?? null)) {
            return ['action' => 'skipped', 'error' => $fakerGuard->validationMessage()];
        }

        if (! app(MasterDataProtection::class)->allowsWrite('import')) {
            app(MasterDataAudit::class)->serviceRecreationBlocked($code, 'import', 'Master data protection is enabled.');

            return ['action' => 'skipped', 'error' => __('Import blocked by master data protection.')];
        }

        $guard = app(ServiceCreationGuard::class);
        $guard->resolveForExplicitRecreate($code, 'import');
        $existing = Service::query()->where('service_code', $code)->first();
        $previous = $existing?->toArray();

        if ($existing === null && ! $guard->canCreateService($code, 'import')) {
            return ['action' => 'skipped', 'error' => __('Service permanently deleted; import skipped.')];
        }

        $attrs = $this->fieldMapper->serviceAttributes($row);
        $attrs['title'] = $title;
        $attrs['service_code'] = $code;

        if (filled($row['publish_status'] ?? null)) {
            $attrs['publish_status'] = PublishStatus::tryFrom(strtolower($row['publish_status'])) ?? PublishStatus::Published;
        } elseif ($existing === null) {
            $attrs['publish_status'] = PublishStatus::Published;
        }

        if (filled($row['visibility'] ?? null)) {
            $attrs['visibility'] = ServiceVisibility::tryFrom(strtolower($row['visibility'])) ?? ServiceVisibility::Public;
        } elseif ($existing === null) {
            $attrs['visibility'] = ServiceVisibility::Public;
        }

        if ($existing === null) {
            if (isset($attrs['custom_fields']) && is_array($attrs['custom_fields'])) {
                // no merge needed on create
            }
            $service = Service::query()->create($attrs);
            $this->recorder->record('created', 'service', $service->id, null, $line);
            app(MasterDataAudit::class)->serviceCreated($service, 'import');
            $action = 'created';
        } else {
            if (isset($attrs['custom_fields']) && is_array($attrs['custom_fields'])) {
                $attrs['custom_fields'] = array_merge($existing->custom_fields ?? [], $attrs['custom_fields']);
            }
            $existing->update($attrs);
            $service = $existing->fresh();
            $this->recorder->record('updated', 'service', $service->id, $previous, $line);
            app(MasterDataAudit::class)->serviceUpdated($service, 'import');
            $action = 'updated';
        }

        $this->syncCategories($service, $row);
        $this->fieldMapper->syncSeo($service, $row);
        $this->syncFaqs($service, $row);
        $this->fieldMapper->syncSchema($service, $row);
        $this->storeRelatedCodes($service, $row);

        return ['action' => $action, 'error' => null];
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function syncCategories(Service $service, array $row): void
    {
        $codes = ImportSupport::parseList($row['category_codes'] ?? null);
        if (filled($row['primary_category_code'] ?? null)) {
            array_unshift($codes, (string) $row['primary_category_code']);
        }
        $codes = array_values(array_unique(array_filter($codes)));

        if ($codes === []) {
            return;
        }

        $ids = ServiceCategory::query()
            ->whereIn('code', array_map(fn ($c) => ServiceCategory::normalizeCode($c), $codes))
            ->pluck('id')
            ->all();

        if ($ids !== []) {
            $service->categories()->syncWithoutDetaching($ids);
        }
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function syncFaqs(Service $service, array $row): void
    {
        $pairs = ImportSupport::parseFaqPairs($row['faq_pairs'] ?? null);
        if ($pairs === []) {
            return;
        }

        $service->faqs()->delete();

        foreach ($pairs as $pair) {
            ServiceFaq::query()->create([
                'service_id' => $service->id,
                'question' => $pair['question'],
                'answer' => $pair['answer'],
            ]);
        }
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function storeRelatedCodes(Service $service, array $row): void
    {
        $related = ImportSupport::extractCustomFields($row, [
            'related_service_codes',
            'related_category_codes',
            'related_sub_service_codes',
            'related_location_pincode',
        ]);
        if ($related === []) {
            return;
        }

        $custom = array_merge($service->custom_fields ?? [], $related);
        $service->forceFill(['custom_fields' => $custom])->saveQuietly();
        app(\App\Services\Operations\ServiceInternalLinkingEngine::class)->persist($service->fresh());
    }
}
