<?php

namespace App\Console\Commands;

use App\Services\Import\ImportRegistry;
use App\Services\Seo\GeoEnrichmentReadinessService;
use App\Services\Seo\SeoOwnershipGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class Phase1bFoundationReportCommand extends Command
{
    protected $signature = 'medca:phase1b-report {--output= : Markdown output path}';

    protected $description = 'Generate Phase 1B foundation hardening deliverable reports';

    public function handle(
        GeoEnrichmentReadinessService $geoReadiness,
        ImportRegistry $importRegistry,
    ): int {
        $report = [
            'generated_at' => now()->toIso8601String(),
            'sub_service_architecture' => [
                'tables' => ['sub_services', 'sub_service_seo', 'sub_service_schema', 'sub_service_faqs'],
                'parent_relationship' => 'sub_services.service_id → services.id',
                'standalone_promotion' => 'sub_services.standalone_service_id → services.id',
                'schema_integration' => 'UnifiedJsonLdGraphBuilder hasPart nodes',
            ],
            'seo_ownership' => [
                'operations_canonical' => SeoOwnershipGuard::operationsOwnsServiceUrls(),
                'mirror_to_growth_layer' => SeoOwnershipGuard::shouldMirrorServiceToGrowthLayer(),
                'canonical_service_source' => SeoOwnershipGuard::canonicalSourceForService(),
                'generated_schema_source' => SeoOwnershipGuard::generatedSchemaSource(),
            ],
            'matrix_architecture' => [
                'pivot_table' => 'service_pincodes',
                'pivot_fields' => ['priority', 'is_visible', 'is_featured', 'coverage_notes', 'category_filter_ids', 'effective_from', 'effective_until'],
                'mapping_table' => 'service_location_pages',
                'reconcile_command' => 'medca:reconcile-service-location-matrix',
            ],
            'geo_enrichment' => $geoReadiness->audit(),
            'import_readiness' => $importRegistry->readinessMatrix(),
            'database_first' => [
                'locality_resolver' => \App\Services\Seo\LocalityContextResolver::class,
                'hardcoded_locality_in_app_php' => 'removed_from_core_engines',
            ],
        ];

        $path = $this->option('output') ?: base_path('docs/PHASE-1B-FOUNDATION-REPORTS.md');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $this->toMarkdown($report));

        $this->info("Phase 1B report written to {$path}");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function toMarkdown(array $report): string
    {
        $lines = ['# Phase 1B Foundation Reports', '', '```json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), '```'];

        return implode("\n", $lines)."\n";
    }
}
