<?php

namespace App\Services\Import;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Service;
use App\Models\SubService;
use App\Models\SubServiceFaq;
use App\Models\SubServiceSchema;
use App\Models\SubServiceSeo;

final class SubServiceEntityImporter extends AbstractSpreadsheetImporter
{
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
            'description', 'short_summary', 'sort_order', 'is_featured', 'is_top_rated',
            'is_active', 'show_on_homepage', 'show_on_about', 'show_on_contact',
            'publish_status', 'visibility', 'meta_title', 'meta_description',
            'focus_keywords', 'secondary_keywords', 'faq_pairs', 'ai_summary',
            'h1', 'h2_lines', 'h3_lines', 'schema_json_override',
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

        $existing = SubService::query()
            ->where('service_id', $parent->id)
            ->where('sub_service_code', $subCode)
            ->first();

        $previous = $existing?->toArray();

        $attrs = [
            'service_id' => $parent->id,
            'sub_service_code' => $subCode,
            'title' => $title,
            'description' => $row['description'] ?? null,
            'short_summary' => $row['short_summary'] ?? null,
            'sort_order' => filled($row['sort_order'] ?? null) ? (int) $row['sort_order'] : 0,
            'is_active' => ImportSupport::parseBool($row['is_active'] ?? null, true),
            'is_featured' => ImportSupport::parseBool($row['is_featured'] ?? null),
            'is_top_rated' => ImportSupport::parseBool($row['is_top_rated'] ?? null),
            'show_on_homepage' => ImportSupport::parseBool($row['show_on_homepage'] ?? null),
            'show_on_about' => ImportSupport::parseBool($row['show_on_about'] ?? null),
            'show_on_contact' => ImportSupport::parseBool($row['show_on_contact'] ?? null),
            'ai_summary' => $row['ai_summary'] ?? null,
            'publish_status' => PublishStatus::tryFrom(strtolower($row['publish_status'] ?? '')) ?? PublishStatus::Published,
            'visibility' => ServiceVisibility::tryFrom(strtolower($row['visibility'] ?? '')) ?? ServiceVisibility::Public,
        ];

        if ($existing === null) {
            $sub = SubService::query()->create($attrs);
            $this->recorder->record('created', 'sub_service', $sub->id, null, $line);
            $action = 'created';
        } else {
            $existing->update($attrs);
            $sub = $existing->fresh();
            $this->recorder->record('updated', 'sub_service', $sub->id, $previous, $line);
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
        $entityTags = array_filter([
            'h2_lines' => $row['h2_lines'] ?? null,
            'h3_lines' => $row['h3_lines'] ?? null,
        ], static fn ($v) => $v !== null && $v !== '');

        $seoFields = array_filter([
            'meta_title' => $row['meta_title'] ?? null,
            'meta_description' => $row['meta_description'] ?? null,
            'focus_keywords' => ImportSupport::parseKeywords($row['focus_keywords'] ?? null),
            'secondary_keywords' => ImportSupport::parseKeywords($row['secondary_keywords'] ?? null),
            'h1' => $row['h1'] ?? null,
            'entity_tags' => $entityTags !== [] ? $entityTags : null,
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
        $override = ImportSupport::parseJson($row['schema_json_override'] ?? null);
        if ($override === null) {
            return;
        }

        SubServiceSchema::query()->updateOrCreate(
            ['sub_service_id' => $sub->id],
            ['schema_type' => 'Service', 'schema_json' => $override]
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
