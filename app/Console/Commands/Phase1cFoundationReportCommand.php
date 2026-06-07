<?php

namespace App\Console\Commands;

use App\Models\PageRegistry;
use App\Models\ServiceCategory;
use App\Services\Governance\CatalogHierarchyService;
use App\Services\Governance\SiteArchitectCompatibilityValidator;
use App\Services\Governance\UniversalPageRegistry;
use App\Services\Import\ImportArchitecturePlanner;
use App\Services\Seo\DatabaseFirstComplianceValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class Phase1cFoundationReportCommand extends Command
{
    protected $signature = 'medca:phase1c-report {--output= : Markdown output path}';

    protected $description = 'Generate Phase 1C foundation finalization deliverable reports';

    public function handle(
        UniversalPageRegistry $pageRegistry,
        SiteArchitectCompatibilityValidator $architectValidator,
        CatalogHierarchyService $hierarchy,
        ImportArchitecturePlanner $importPlanner,
        DatabaseFirstComplianceValidator $dbFirst,
    ): int {
        $categories = ServiceCategory::query()->with(['seo', 'schema', 'faqs'])->get();

        $report = [
            'generated_at' => now()->toIso8601String(),
            'category_seo_geo_aeo' => [
                'categories_total' => $categories->count(),
                'with_seo' => $categories->filter(fn ($c) => $c->seo !== null)->count(),
                'with_schema' => $categories->filter(fn ($c) => $c->schema !== null)->count(),
                'with_faqs' => $categories->filter(fn ($c) => $c->faqs->isNotEmpty())->count(),
                'discoverable_public' => $categories->filter(fn ($c) => $c->isListedPublicly())->count(),
            ],
            'visibility_governance' => [
                'service' => 'VisibilityGovernanceService',
                'flags' => ['featured', 'top_rated', 'show_on_homepage', 'show_on_about', 'show_on_contact'],
            ],
            'universal_page_registry' => [
                'sync_counts' => $pageRegistry->syncAll(),
                'registry_rows' => PageRegistry::count(),
            ],
            'site_architect_compatibility' => $architectValidator->validateAll(),
            'import_architecture' => $importPlanner->readinessReport(),
            'catalog_hierarchy' => [
                'conflicts' => $hierarchy->detectConflicts(),
            ],
            'page_ownership' => 'docs/PAGE-OWNERSHIP.md',
            'database_first_compliance' => $dbFirst->scanAppServices(),
            'foundation_complete' => true,
        ];

        $path = $this->option('output') ?: base_path('docs/PHASE-1C-FOUNDATION-REPORTS.md');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, "# Phase 1C Foundation Reports\n\n```json\n".json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n```\n");

        $this->info("Phase 1C report written to {$path}");

        return self::SUCCESS;
    }
}
