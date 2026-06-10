<?php

namespace App\Services\Import;

use App\Models\Service;
use App\Models\ServiceSchema;
use App\Models\ServiceSeo;

/**
 * Maps master workbook service columns into existing Operations models.
 */
final class ImportServiceFieldMapper
{
    /** @var list<string> */
    private const CUSTOM_FIELD_KEYS = [
        'preparation', 'duration', 'requirements', 'deliverables',
        'coverage_notes', 'emergency_coverage_notes',
        'show_on_category_pages', 'show_on_location_pages', 'display_priority',
        'location_h1_template', 'location_h2_template', 'location_h3_template',
        'location_intro_template', 'location_description_template', 'location_faq_template',
        'location_cta_heading', 'location_cta_content',
        'location_meta_title_template', 'location_meta_description_template',
        'h4_lines', 'h5_lines', 'h6_lines',
        'voice_search_queries', 'conversational_queries', 'entity_references',
        'ai_recommendation_summary',
        'related_service_codes', 'related_category_codes', 'related_sub_service_codes', 'related_location_pincode',
        'banner_image_url', 'gallery_image_urls', 'video_url',
        'twitter_title', 'twitter_description', 'twitter_image', 'breadcrumb_title',
    ];

    /**
     * @param  array<string, string|null>  $row
     * @return array<string, mixed>
     */
    public function serviceAttributes(array $row): array
    {
        $attrs = array_filter([
            'title' => $row['title'] ?? null,
            'service_code' => $row['service_code'] ?? null,
            'description' => $row['description'] ?? null,
            'short_summary' => $row['short_summary'] ?? null,
            'key_benefits' => self::lineArrayOrNull($row['key_benefits'] ?? null),
            'eligibility' => self::lineArrayOrNull($row['eligibility'] ?? null),
            'process_steps' => self::lineArrayOrNull($row['process_steps'] ?? null),
            'trust_signals' => self::lineArrayOrNull($row['trust_signals'] ?? null),
            'procedures' => self::lineArrayOrNull($row['procedures'] ?? null),
            'specialized_care' => self::lineArrayOrNull($row['specialized_care'] ?? null),
            'shifts' => self::lineArrayOrNull($row['shifts'] ?? null),
            'ai_summary' => $row['ai_summary'] ?? null,
            'target_keywords' => ImportSupport::parseKeywords($row['target_keywords'] ?? null),
            'ai_keywords' => ImportSupport::parseKeywords($row['ai_keywords'] ?? null),
            'featured_image' => $row['featured_image_url'] ?? null,
            'icon' => $row['icon_url'] ?? null,
            'image_alt' => $row['image_alt'] ?? null,
            'is_active' => array_key_exists('is_active', $row) ? ImportSupport::parseBool($row['is_active'], true) : null,
            'is_featured' => array_key_exists('is_featured', $row) ? ImportSupport::parseBool($row['is_featured']) : null,
            'is_top_rated' => array_key_exists('is_top_rated', $row) ? ImportSupport::parseBool($row['is_top_rated']) : null,
            'show_on_homepage' => array_key_exists('show_on_homepage', $row) ? ImportSupport::parseBool($row['show_on_homepage']) : null,
            'show_on_about' => array_key_exists('show_on_about', $row) ? ImportSupport::parseBool($row['show_on_about']) : null,
            'show_on_contact' => array_key_exists('show_on_contact', $row) ? ImportSupport::parseBool($row['show_on_contact']) : null,
            'sort_order' => filled($row['sort_order'] ?? null) ? (int) $row['sort_order'] : null,
        ], static fn ($v) => $v !== null);

        $gallery = ImportSupport::parseList($row['gallery_image_urls'] ?? null, '|');
        if ($gallery !== []) {
            $attrs['gallery'] = $gallery;
        }

        $custom = ImportSupport::extractCustomFields($row, self::CUSTOM_FIELD_KEYS);
        if ($custom !== []) {
            $attrs['custom_fields'] = $custom;
        }

        return $attrs;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    public function syncSeo(Service $service, array $row): void
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
            'h2' => self::lineArrayOrNull($row['h2_lines'] ?? null),
            'h3' => self::lineArrayOrNull($row['h3_lines'] ?? null),
            'ai_context' => $row['ai_context'] ?? null,
            'search_intent' => $row['search_intent'] ?? null,
        ], static fn ($v) => $v !== null && $v !== '');

        if ($seoFields === []) {
            return;
        }

        ServiceSeo::query()->updateOrCreate(['service_id' => $service->id], $seoFields);
    }

    /**
     * @param  array<string, string|null>  $row
     */
    public function syncSchema(Service $service, array $row): void
    {
        $schemaType = $row['schema_type'] ?? null;
        $override = ImportSupport::parseJson($row['schema_json_override'] ?? null);

        if (($schemaType === null || $schemaType === '') && $override === null) {
            return;
        }

        ServiceSchema::query()->updateOrCreate(
            ['service_id' => $service->id],
            [
                'schema_type' => filled($schemaType) ? $schemaType : 'Service',
                'schema_json' => $override ?? [],
            ]
        );
    }

    /**
     * @return list<string>|null
     */
    private static function lineArrayOrNull(mixed $value): ?array
    {
        $lines = ImportSupport::normalizeLineArray($value);

        return $lines === [] ? null : $lines;
    }
}
