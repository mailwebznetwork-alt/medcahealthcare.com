<?php

namespace App\Services\Import;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Service;
use App\Models\SubService;
use App\Models\SubServiceFaq;
use App\Models\SubServiceSchema;
use App\Models\SubServiceSeo;
use App\Services\Governance\MasterDataAudit;
use App\Services\Governance\MasterDataProtection;
use App\Services\Governance\SubServiceCreationGuard;

final class SubServiceEntityImporter extends AbstractSpreadsheetImporter
{
    public function __construct(
        SpreadsheetReader $reader,
        ImportBatchRecorder $recorder,
        private readonly ImportCatalogContentMapper $contentMapper,
    ) {
        parent::__construct($reader, $recorder);
    }

    public function entityKey(): string
    {
        return 'sub_services';
    }

    protected function requiredColumns(): array
    {
        return ['parent_service_code', 'sub_service_code', 'title'];
    }

    protected function optionalColumns(): array
    {
        return [
            'description', 'short_summary', 'key_benefits', 'eligibility', 'process_steps', 'ai_summary',
            'procedures', 'specialized_care', 'shifts', 'price_range', 'trust_signals',
            'target_keywords', 'ai_keywords', 'sort_order', 'is_featured', 'is_top_rated',
            'is_active', 'show_on_homepage', 'show_on_about', 'show_on_contact',
            'publish_status', 'visibility', 'meta_title', 'meta_description',
            'focus_keywords', 'secondary_keywords', 'canonical_url', 'robots_index',
            'og_title', 'og_description', 'og_image', 'search_intent', 'ai_context',
            'faq_pairs', 'h1', 'h2_lines', 'h3_lines', 'schema_type', 'schema_json_override',
            'featured_image_url', 'icon_url', 'gallery_image_urls', 'image_alt',
        ];
    }

    protected function previewRow(array $row, int $line): array
    {
        $parentCode = trim((string) ($row['parent_service_code'] ?? ''));
        $subCode = trim((string) ($row['sub_service_code'] ?? ''));
        if ($parentCode === '' || $subCode === '') {
            return ['status' => 'invalid', 'detail' => __('Missing parent or sub service code.'), 'key' => null];
        }

        $parent = Service::query()->where('service_code', $parentCode)->first();
        if ($parent === null) {
            if (app(WorkbookImportContext::class)->hasPendingServiceCode($parentCode)) {
                return [
                    'status' => 'ready',
                    'detail' => __('Parent service will be created from the Services sheet in this workbook.'),
                    'key' => "{$parentCode}/{$subCode}",
                ];
            }

            return ['status' => 'invalid', 'detail' => __('Parent service not found.'), 'key' => null];
        }

        $exists = SubService::query()
            ->where('service_id', $parent->id)
            ->where('sub_service_code', $subCode)
            ->exists();

        return [
            'status' => $exists ? 'update' : 'ready',
            'detail' => $exists ? __('Will update existing sub-service.') : null,
            'key' => "{$parentCode}/{$subCode}",
        ];
    }

    protected function importRow(array $row, int $line): array
    {
        $parentCode = trim((string) ($row['parent_service_code'] ?? ''));
        $subCode = trim((string) ($row['sub_service_code'] ?? ''));
        $title = trim((string) ($row['title'] ?? ''));

        $parent = Service::query()->where('service_code', $parentCode)->first();
        if ($parent === null) {
            return ['action' => 'failed', 'error' => __('Parent service not found: :code', ['code' => $parentCode])];
        }

        if ($subCode === '' || $title === '') {
            return ['action' => 'failed', 'error' => __('Missing sub_service_code or title.')];
        }

        if (! app(MasterDataProtection::class)->allowsWrite('import')) {
            app(MasterDataAudit::class)->subServiceRecreationBlocked(
                SubServiceCreationGuard::naturalKey($parentCode, $subCode),
                'import',
                'Master data protection is enabled.',
            );

            return ['action' => 'skipped', 'error' => __('Import blocked by master data protection.')];
        }

        $guard = app(SubServiceCreationGuard::class);
        $guard->resolveForExplicitRecreate($parentCode, $subCode, 'import');

        $existing = SubService::query()
            ->where('service_id', $parent->id)
            ->where('sub_service_code', $subCode)
            ->first();

        if ($existing === null && ! $guard->canCreateSubService($parentCode, $subCode, 'import')) {
            return ['action' => 'skipped', 'error' => __('Sub-service permanently deleted; import skipped.')];
        }

        $previous = $existing?->toArray();

        $attrs = array_merge([
            'service_id' => $parent->id,
            'sub_service_code' => $subCode,
            'title' => $title,
            'sort_order' => filled($row['sort_order'] ?? null) ? (int) $row['sort_order'] : 0,
            'is_active' => ImportSupport::parseBool($row['is_active'] ?? null, true),
            'is_featured' => ImportSupport::parseBool($row['is_featured'] ?? null),
            'is_top_rated' => ImportSupport::parseBool($row['is_top_rated'] ?? null),
            'show_on_homepage' => ImportSupport::parseBool($row['show_on_homepage'] ?? null),
            'show_on_about' => ImportSupport::parseBool($row['show_on_about'] ?? null),
            'show_on_contact' => ImportSupport::parseBool($row['show_on_contact'] ?? null),
            'publish_status' => PublishStatus::tryFrom(strtolower($row['publish_status'] ?? '')) ?? PublishStatus::Published,
            'visibility' => ServiceVisibility::tryFrom(strtolower($row['visibility'] ?? '')) ?? ServiceVisibility::Public,
        ], $this->contentMapper->contentAttributes($row));

        if ($existing === null) {
            $sub = SubService::query()->create($attrs);
            $this->recorder->record('created', 'sub_service', $sub->id, null, $line);
            app(MasterDataAudit::class)->subServiceCreated($sub, 'import');
            $action = 'created';
        } else {
            $existing->update($attrs);
            $sub = $existing->fresh();
            $this->recorder->record('updated', 'sub_service', $sub->id, $previous, $line);
            app(MasterDataAudit::class)->subServiceUpdated($sub, 'import');
            $action = 'updated';
        }

        $this->syncSeo($sub, $row);
        $this->syncFaqs($sub, $row);
        $this->syncSchema($sub, $row);

        return ['action' => $action, 'error' => null];
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function syncSeo(SubService $sub, array $row): void
    {
        $seoFields = array_filter([
            'meta_title' => $row['meta_title'] ?? null,
            'meta_description' => $row['meta_description'] ?? null,
            'focus_keywords' => ImportSupport::parseKeywords($row['focus_keywords'] ?? null),
            'secondary_keywords' => ImportSupport::parseKeywords($row['secondary_keywords'] ?? null),
            'canonical_url' => $row['canonical_url'] ?? null,
            'robots_index' => array_key_exists('robots_index', $row)
                ? ImportSupport::parseBool($row['robots_index'], true)
                : null,
            'og_title' => $row['og_title'] ?? null,
            'og_description' => $row['og_description'] ?? null,
            'og_image' => $row['og_image'] ?? null,
            'h1' => $row['h1'] ?? null,
            'h2' => ImportSupport::normalizeLineArray($row['h2_lines'] ?? null) ?: null,
            'h3' => ImportSupport::normalizeLineArray($row['h3_lines'] ?? null) ?: null,
            'search_intent' => $row['search_intent'] ?? null,
            'ai_context' => $row['ai_context'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        if ($seoFields === []) {
            return;
        }

        SubServiceSeo::query()->updateOrCreate(['sub_service_id' => $sub->id], $seoFields);
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function syncSchema(SubService $sub, array $row): void
    {
        $schemaType = $row['schema_type'] ?? null;
        $override = ImportSupport::parseJson($row['schema_json_override'] ?? null);

        if (($schemaType === null || $schemaType === '') && $override === null) {
            return;
        }

        SubServiceSchema::query()->updateOrCreate(
            ['sub_service_id' => $sub->id],
            [
                'schema_type' => filled($schemaType) ? $schemaType : 'Service',
                'schema_json' => $override ?? [],
            ]
        );
    }

    private function syncFaqs(SubService $sub, array $row): void
    {
        $pairs = ImportSupport::parseFaqPairs($row['faq_pairs'] ?? null);
        if ($pairs === []) {
            return;
        }

        foreach ($pairs as $i => $pair) {
            SubServiceFaq::query()->updateOrCreate(
                ['sub_service_id' => $sub->id, 'question' => $pair['question']],
                ['answer' => $pair['answer'], 'sort_order' => $i]
            );
        }
    }
}
