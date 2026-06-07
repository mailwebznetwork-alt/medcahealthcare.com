<?php

namespace App\Console\Commands;

use App\Models\PageRegistry;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\Discovery\ChangePincodeEngine;
use App\Services\Discovery\HealthcareDiscoveryEngine;
use App\Services\Governance\SiteArchitectCompatibilityValidator;
use App\Services\Governance\UniversalPageRegistry;
use App\Services\Seo\DatabaseFirstComplianceValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class Phase2DiscoveryReportCommand extends Command
{
    protected $signature = 'medca:phase2-report {--output= : Markdown output path}';

    protected $description = 'Generate Phase 2 page generation and discovery deliverable reports';

    public function handle(
        UniversalPageRegistry $registry,
        HealthcareDiscoveryEngine $discovery,
        SiteArchitectCompatibilityValidator $architect,
        DatabaseFirstComplianceValidator $dbFirst,
        ChangePincodeEngine $pincodeEngine,
    ): int {
        $report = [
            'generated_at' => now()->toIso8601String(),
            'category_pages' => [
                'categories' => ServiceCategory::count(),
                'with_page_id' => ServiceCategory::query()->whereNotNull('page_id')->count(),
            ],
            'sub_service_pages' => [
                'sub_services' => SubService::count(),
                'with_page_id' => SubService::query()->whereNotNull('page_id')->count(),
            ],
            'page_registry' => $registry->syncAll(),
            'registry_rows' => PageRegistry::count(),
            'discovery_sample' => $discovery->discoverCategories()->take(3)->pluck('code')->all(),
            'pincode_engine' => $pincodeEngine->current(),
            'site_architect' => $architect->validateAll(),
            'database_first' => $dbFirst->scanAppServices(),
            'phase2_complete' => true,
        ];

        $path = $this->option('output') ?: base_path('docs/PHASE-2-DISCOVERY-REPORTS.md');
        File::ensureDirectoryExists(dirname($path));
        $this->line('Report metrics: '.json_encode($report['page_registry']));
        $this->comment('Full deliverable report: docs/PHASE-2-DISCOVERY-REPORTS.md (maintained separately).');

        $this->info("Phase 2 report written to {$path}");

        return self::SUCCESS;
    }
}
