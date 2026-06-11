<?php

namespace App\Services\Seo;

use App\Models\Page;

/**
 * Canonical SEO ownership hierarchy (Operations-first).
 *
 * Priority (highest wins for public output):
 * 1. Linked Page with explicit meta override (ServicePageOverrides)
 * 2. service_seo (Operations master for services)
 * 3. Runtime UnifiedJsonLdGraphBuilder (generated schema — read-only output)
 * 4. page_seo / page_elements (Growth legacy mirror — write suppressed when operations_canonical)
 */
final class SeoOwnershipGuard
{
    public static function operationsOwnsServiceUrls(): bool
    {
        return (bool) config('services_master.seo_ownership.operations_canonical', true);
    }

    public static function shouldMirrorServiceToGrowthLayer(): bool
    {
        return (bool) config('services_master.seo_ownership.mirror_to_growth_layer', false);
    }

    public static function canonicalSourceForService(): string
    {
        return 'service_seo';
    }

    public static function canonicalSourceForServicePage(): string
    {
        return 'pages_with_operations_sync';
    }

    public static function canonicalSourceForLocationPage(): string
    {
        return 'pages_with_pincode_enrichment';
    }

    public static function generatedSchemaSource(): string
    {
        return 'unified_json_ld_graph_builder';
    }

    public static function skipAutofillOnGeneratedPages(): bool
    {
        return (bool) config('seo_ownership.skip_autofill_on_generated_pages', true);
    }

    public static function skipLocationMetaDuplicates(): bool
    {
        return (bool) config('seo_ownership.skip_location_meta_duplicates', true);
    }

    public static function skipPageSeoForGeneratedPages(): bool
    {
        return (bool) config('seo_ownership.skip_page_seo_for_generated_pages', true);
    }

    public static function hideSeoEditingOnGeneratedPages(): bool
    {
        return (bool) config('seo_ownership.hide_seo_editing_on_generated_pages', true);
    }

    public static function isGeneratedPage(?Page $page): bool
    {
        return $page !== null && $page->page_source === 'generated';
    }
}
