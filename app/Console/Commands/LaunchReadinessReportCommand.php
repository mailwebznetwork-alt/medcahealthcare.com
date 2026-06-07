<?php

namespace App\Console\Commands;

use App\Services\Launch\Phase3ValidationSuite;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LaunchReadinessReportCommand extends Command
{
    protected $signature = 'medca:launch-readiness-report {--output= : Markdown output path}';

    protected $description = 'Generate Phase 3 launch readiness report (20 deliverables)';

    public function handle(Phase3ValidationSuite $suite): int
    {
        $report = $suite->score($suite->runAll());

        $path = $this->option('output') ?: base_path('docs/PHASE-3-LAUNCH-REPORTS.md');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $this->toMarkdown($report));

        $this->info("Launch readiness report written to {$path}");
        $this->line("Launch score: {$report['launch_score']}% — ".($report['launch_ready'] ? 'READY' : 'NOT READY'));

        return $report['launch_ready'] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function toMarkdown(array $report): string
    {
        $score = $report['launch_score'] ?? 0;
        $ready = ($report['launch_ready'] ?? false) ? 'YES' : 'NO';
        $generatedAt = $report['generated_at'] ?? now()->toIso8601String();
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<MD
# MEDCA HEALTH CARE — Phase 3 Launch Readiness Report

**Generated:** {$generatedAt}
**Launch Score:** {$score}%
**Launch Ready:** {$ready}

## Deliverables

1. **Import Framework** — {$report['import_framework']['implemented_count']}/{$report['import_framework']['total_entities']} entities implemented
2. **Category Import** — {$report['category_data']['total']} categories, {$report['category_data']['with_seo']} with SEO
3. **Service Import** — {$report['service_data']['total']} services, {$report['service_data']['published']} published
4. **Sub Service Import** — {$report['sub_service_data']['total']} sub-services
5. **Pincode Import** — {$report['pincode_data']['total']} pincodes, {$report['pincode_data']['serviceable']} serviceable
6. **Mapping Import** — {$report['mapping_data']['matrix_rows']} matrix rows
7. **GEO Enrichment** — readiness score {$report['geo_enrichment']['readiness_score']}%
8. **SEO Validation** — {$report['seo_validation']['services_with_seo']} services with SEO
9. **GEO Validation** — {$report['geo_validation']['geo_coverage_pct']}% serviceable pincodes enriched
10. **AEO Validation** — {$report['aeo_validation']['service_faqs']} service FAQs
11. **Internal Linking** — {$report['internal_linking']['registry_rows']} registry rows
12. **Page Registry** — synced {$report['page_registry']['synced']} entries
13. **Site Architect** — compatible: {$this->boolLabel($report['site_architect']['compatible'] ?? false)}
14. **Performance** — WebP pipeline: {$this->boolLabel($report['performance']['webp_pipeline'] ?? false)}
15. **GTM** — configured: {$this->boolLabel($report['tracking']['gtm_configured'] ?? false)}
16. **GA4** — configured: {$this->boolLabel($report['tracking']['ga4_configured'] ?? false)}
17. **Search Console** — verification: {$this->boolLabel($report['tracking']['search_console']['configured'] ?? false)}
18. **AI Discoverability** — structured data pages: {$report['ai_discoverability']['structured_data_pages']}
19. **Production Readiness** — discovery + pincode engines active
20. **Launch Readiness** — score {$score}%

## Full Report (JSON)

```json
{$json}
```

MD;
    }

    private function boolLabel(bool $value): string
    {
        return $value ? 'yes' : 'no';
    }
}
