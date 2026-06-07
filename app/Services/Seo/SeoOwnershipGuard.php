<?php

namespace App\Services\Seo;

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
}
