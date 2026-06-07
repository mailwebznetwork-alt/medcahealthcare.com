<?php

namespace App\Services\Import;

use App\Models\ServiceCategory;
use App\Models\ServiceCategorySeo;

final class ImportCategoryFieldMapper
{
    /**
     * @param  array<string, string|null>  $row
     * @return array<string, mixed>
     */
    public function categoryAttributes(array $row, ?int $parentId): array
    {
        return array_filter([
            'name' => $row['name'] ?? null,
            'code' => $row['code'] ?? null,
            'slug' => $row['slug'] ?? null,
            'description' => $row['description'] ?? null,
            'parent_id' => $parentId,
            'sort_order' => filled($row['sort_order'] ?? null) ? (int) $row['sort_order'] : null,
            'is_active' => array_key_exists('is_active', $row) ? ImportSupport::parseBool($row['is_active'], true) : null,
            'is_featured' => array_key_exists('is_featured', $row) ? ImportSupport::parseBool($row['is_featured']) : null,
            'show_on_homepage' => array_key_exists('show_on_homepage', $row) ? ImportSupport::parseBool($row['show_on_homepage']) : null,
            'show_on_about' => array_key_exists('show_on_about', $row) ? ImportSupport::parseBool($row['show_on_about']) : null,
            'show_on_contact' => array_key_exists('show_on_contact', $row) ? ImportSupport::parseBool($row['show_on_contact']) : null,
        ], static fn ($v) => $v !== null);
    }

    /**
     * @param  array<string, string|null>  $row
     */
    public function syncSeo(ServiceCategory $category, array $row): void
    {
        $entityTags = array_filter([
            'h1' => $row['h1'] ?? null,
            'breadcrumb_title' => $row['breadcrumb_title'] ?? null,
        ], static fn ($v) => $v !== null && $v !== '');

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
            'aeo_question' => $row['aeo_question'] ?? null,
            'aeo_answer' => $row['aeo_answer'] ?? null,
            'entity_tags' => $entityTags !== [] ? $entityTags : null,
        ], static fn ($v) => $v !== null && $v !== '');

        if ($seoFields === []) {
            return;
        }

        ServiceCategorySeo::query()->updateOrCreate(
            ['service_category_id' => $category->id],
            $seoFields
        );
    }
}
