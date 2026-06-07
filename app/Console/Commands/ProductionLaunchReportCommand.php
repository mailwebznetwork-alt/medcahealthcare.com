<?php

namespace App\Console\Commands;

use App\Services\Launch\ProductionLaunchAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ProductionLaunchReportCommand extends Command
{
    protected $signature = 'medca:production-launch-report {--output= : Markdown output path}';

    protected $description = 'Production readiness audit and final launch report (20 deliverables)';

    public function handle(ProductionLaunchAuditService $audit): int
    {
        $report = $audit->audit();
        $path = $this->option('output') ?: base_path('docs/PRODUCTION-LAUNCH-REPORT.md');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $this->markdown($report));

        $scores = $report['scores'] ?? [];
        $this->info("Report: {$path}");
        $this->table(['Score', 'Value'], collect($scores)->map(fn ($v, $k) => [$k, $v])->values()->all());
        $this->line('Launch ready: '.($report['launch_ready'] ? 'YES' : 'NO'));

        return ($report['launch_ready'] ?? false) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private function markdown(array $r): string
    {
        $t = $r['totals'] ?? [];
        $s = $r['scores'] ?? [];

        return <<<MD
# MEDCA HEALTH CARE — Production Launch Report

**Generated:** {$r['generated_at']}
**Launch Ready:** {$this->yn($r['launch_ready'] ?? false)}

## Totals

| Metric | Count |
|--------|------:|
| Categories | {$t['categories']} |
| Services | {$t['services']} |
| Sub Services | {$t['sub_services']} |
| Pincodes | {$t['pincodes']} |
| Serviceable Pincodes | {$t['serviceable_pincodes']} |
| Service↔Pincode Mappings | {$t['mappings']} |
| Generated Pages | {$t['generated_pages']} |
| Location Pages | {$t['location_pages']} |
| FAQs (all types) | {$t['faqs']} |
| Schema Pages | {$t['schema_pages']} |
| Internal Link Snapshots | {$t['internal_link_snapshots']} |
| Registry Rows | {$t['registry_rows']} |

## Scores

| Dimension | Score |
|-----------|------:|
| SEO | {$s['seo']} |
| GEO | {$s['geo']} |
| AEO | {$s['aeo']} |
| Performance | {$s['performance']} |
| Tracking | {$s['tracking']} |
| Content | {$s['content']} |
| Discovery | {$s['discovery']} |
| **Launch** | **{$s['launch']}** |

## Validation Summary

- **Category pages:** SEO/GEO/AEO via expansion engines + registry
- **Service pages:** Orchestrator sync + location matrix
- **Sub-service pages:** Independent pages with parent linkage
- **Location pages:** Reconciled from service↔pincode matrix
- **Change pincode:** Switch success = {$this->yn($r['pincode_validation']['switch_success'] ?? false)}
- **Site Architect:** Compatible = {$this->yn($r['site_architect']['compatible'] ?? false)}
- **GEO coverage:** {$r['geo_coverage']['pct']}% serviceable pincodes enriched

## Tracking Status

- GTM: {$this->yn($r['tracking']['gtm_configured'] ?? false)}
- GA4: {$this->yn($r['tracking']['ga4_configured'] ?? false)}
- WhatsApp: {$this->yn($r['tracking']['whatsapp_ready'] ?? false)}
- Search Console token: {$this->yn($r['tracking']['search_console']['configured'] ?? false)}

## Commands

```bash
php artisan medca:populate-production
php artisan medca:production-launch-report
php artisan medca:validate-production-readiness
```

MD;
    }

    private function yn(bool $v): string
    {
        return $v ? 'YES' : 'NO';
    }
}
