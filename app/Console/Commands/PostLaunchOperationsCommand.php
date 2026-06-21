<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Page;
use App\Models\PageRegistry;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SeoTechnical;
use App\Models\SubService;
use App\Services\Growth\SeoService;
use App\Services\Launch\GoLiveCertificationService;
use App\Services\Launch\ProductionTrackingConfigurator;
use App\Services\Launch\TrackingValidationService;
use App\Services\Marketing\Analytics\MarketingAnalyticsAggregator;
use App\Services\Marketing\Analytics\MarketingConversionMetricsService;
use App\Services\Marketing\Ga4DataApiService;
use App\Services\Marketing\LeadIntent\LeadIntentDashboardService;
use App\Models\MarketingSetting;
use App\Services\Seo\GeoEnrichmentReadinessService;
use App\Support\GrowthReadinessReport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Post-launch operations report — orchestrates existing audits only (no new engines).
 */
class PostLaunchOperationsCommand extends Command
{
    protected $signature = 'medca:post-launch-ops
                            {--activate-tracking : Apply MEDCA_* env tracking config to existing integrations}
                            {--days=28 : Lookback window for lead and conversion metrics}
                            {--output= : Markdown report path}';

    protected $description = 'Post-launch operations & growth report (tracking, SEO/GEO/AEO, leads, health audit)';

    public function handle(
        ProductionTrackingConfigurator $trackingConfigurator,
        TrackingValidationService $trackingValidation,
        GoLiveCertificationService $certification,
        MarketingAnalyticsAggregator $marketingAnalytics,
        MarketingConversionMetricsService $conversionMetrics,
        LeadIntentDashboardService $leadIntent,
        GeoEnrichmentReadinessService $geoReadiness,
        Ga4DataApiService $ga4Api,
        SeoService $seoService,
    ): int {
        $days = max(1, (int) $this->option('days'));
        $from = now()->subDays($days)->startOfDay();
        $to = now()->endOfDay();

        if ($this->option('activate-tracking')) {
            $applied = $trackingConfigurator->configure();
            $this->components->info('Tracking activation: '.json_encode($applied));
        }

        $tracking = $trackingValidation->audit();
        $cert = $certification->certify();
        $geo = $geoReadiness->audit();
        $growth = GrowthReadinessReport::build();
        $executive = $marketingAnalytics->executiveSummary($from, $to);
        $whatsappMetrics = $marketingAnalytics->whatsAppMetrics('month');
        $callMetrics = $marketingAnalytics->callMetrics('month');
        $conversions = $conversionMetrics->metrics($from, $to);
        $leadIntentReport = $leadIntent->report($from, $to);
        $ga4 = $ga4Api->fetchReportBundle(MarketingSetting::current(), '28d');
        $indexing = $this->indexingInventory($seoService);
        $contentExpansion = $this->contentExpansionGuide();
        $aiDiscovery = [
            'llm_txt_route' => '/llm.txt',
            'ai_discovery_route' => '/ai-discovery',
            'ai_discovery_enabled' => (bool) SeoTechnical::query()->value('ai_discovery_enabled'),
            'llm_txt_configured' => filled(SeoTechnical::query()->value('llm_txt')),
        ];

        $report = [
            'generated_at' => now()->toIso8601String(),
            'period_days' => $days,
            'tracking_activation' => $this->option('activate-tracking'),
            'tracking' => $tracking,
            'indexing' => $indexing,
            'seo_growth_readiness' => $growth,
            'ga4_api' => $ga4['error'] === null ? [
                'summary' => $ga4['summary'] ?? [],
                'top_pages' => array_slice($ga4['pages'] ?? [], 0, 10),
                'events' => $ga4['events'] ?? [],
            ] : ['error' => $ga4['error']],
            'geo' => $geo,
            'aeo' => $this->aeoSnapshot(),
            'ai_discoverability' => $aiDiscovery,
            'leads' => [
                'executive' => $executive,
                'by_service' => $this->leadsGrouped('service_id'),
                'by_pincode' => $this->leadsGrouped('pin_code_id'),
                'by_landing' => $this->leadsGrouped('landing_page'),
            ],
            'conversions' => [
                'whatsapp_clicks' => $whatsappMetrics,
                'call_clicks' => $callMetrics,
                'forms' => $leadIntentReport['totals']['forms'] ?? 0,
                'pipeline' => $conversions,
                'lead_intent' => $leadIntentReport,
            ],
            'content_expansion' => $contentExpansion,
            'health_audit' => [
                'scores' => $cert['scores'] ?? [],
                'decision' => $cert['decision'] ?? 'UNKNOWN',
                'warnings' => $cert['warnings'] ?? [],
                'critical_issues' => $cert['critical_issues'] ?? [],
            ],
            'manual_ops' => $this->manualOpsChecklist(),
        ];

        $path = $this->option('output') ?: base_path('docs/POST-LAUNCH-OPERATIONS-REPORT.md');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $this->toMarkdown($report));

        $this->info("Post-launch operations report written to {$path}");
        $this->table(['Health dimension', 'Score'], collect($report['health_audit']['scores'] ?? [])->map(fn ($v, $k) => [$k, $v])->values()->all());

        $trackingScore = $cert['scores']['tracking'] ?? 0;
        if ($trackingScore < 80) {
            $this->warn('Tracking score below 80 — set MEDCA_GTM_CONTAINER_ID, MEDCA_GA4_MEASUREMENT_ID, MEDCA_GSC_VERIFICATION in .env then run with --activate-tracking');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function indexingInventory(SeoService $seoService): array
    {
        $categories = Page::query()->where('slug', 'like', 'category-%')->where('is_active', true)->count();
        $services = Page::query()->where('slug', 'like', 'service-%')->where('is_active', true)->count();
        $subServices = Page::query()->where('slug', 'like', 'sub-service-%')->where('is_active', true)->count();
        $locationPages = ServiceLocationPage::query()->where('is_indexable', true)->count();
        $registry = PageRegistry::count();
        $cmsRoots = Page::query()
            ->whereIn('slug', config('public_pages.root_slugs', []))
            ->where('is_active', true)
            ->pluck('slug')
            ->all();

        return [
            'sitemap_public' => $seoService->isSitemapPubliclyAvailable(),
            'sitemap_url' => url('/sitemap.xml'),
            'robots_url' => url('/robots.txt'),
            'active_pages_total' => Page::query()->where('is_active', true)->count(),
            'category_pages' => $categories,
            'service_pages' => $services,
            'sub_service_pages' => $subServices,
            'indexable_location_pages' => $locationPages,
            'registry_entries' => $registry,
            'cms_root_pages' => $cmsRoots,
            'gsc_note' => 'Index coverage (Indexed / Excluded / Crawled-not-indexed) is monitored manually in Google Search Console — no API integration in codebase.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function aeoSnapshot(): array
    {
        $faqCounts = [
            'service_faqs' => Schema::hasTable('service_faqs') ? DB::table('service_faqs')->count() : 0,
            'category_faqs' => Schema::hasTable('service_category_faqs') ? DB::table('service_category_faqs')->count() : 0,
            'sub_service_faqs' => Schema::hasTable('sub_service_faqs') ? DB::table('sub_service_faqs')->count() : 0,
            'pincode_faqs' => Schema::hasTable('pin_code_location_faqs') ? DB::table('pin_code_location_faqs')->count() : 0,
        ];

        $servicesWithAiSummary = Service::query()->whereNotNull('ai_summary')->where('ai_summary', '!=', '')->count();

        return [
            'faq_counts' => $faqCounts,
            'services_with_ai_summary' => $servicesWithAiSummary,
            'aeo_scores_avg' => Schema::hasTable('service_seo')
                ? round((float) DB::table('service_seo')->avg('aeo_score'), 1)
                : null,
            'ai_discovery_scores_avg' => Schema::hasTable('service_seo')
                ? round((float) DB::table('service_seo')->avg('ai_discovery_score'), 1)
                : null,
        ];
    }

    /**
     * @return list<array{label: string, total: int}>
     */
    private function leadsGrouped(string $column): array
    {
        if (! Schema::hasTable('leads') || ! Schema::hasColumn('leads', $column)) {
            return [];
        }

        return Lead::query()
            ->select($column, DB::raw('count(*) as total'))
            ->whereNotNull($column)
            ->groupBy($column)
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => ['label' => (string) $row->{$column}, 'total' => (int) $row->total])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function contentExpansionGuide(): array
    {
        return [
            'import_command' => 'php artisan medca:import {entity} {file}',
            'entities' => array_keys(array_filter(
                config('import_registry.entities', []),
                fn (array $e) => ($e['status'] ?? '') === 'implemented'
            )),
            'formats' => ['csv', 'xls', 'xlsx'],
            'production_path' => config('medca_launch.imports_path'),
            'full_populate' => 'php artisan medca:populate-production',
            'counts' => [
                'categories' => ServiceCategory::count(),
                'services' => Service::count(),
                'sub_services' => SubService::count(),
                'pincodes' => \App\Models\PinCode::count(),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function manualOpsChecklist(): array
    {
        return [
            'Submit sitemap.xml in Google Search Console (Property → Sitemaps).',
            'Mark phone_click, whatsapp_click, form_submit as GA4 conversions (Admin → Events).',
            'Verify GTM container publishes tags for GA4 + conversion events.',
            'Review GSC Index Coverage weekly: Indexed, Excluded, Crawled-not-indexed, redirects, canonicals.',
            'Monitor AI visibility manually: ChatGPT/Gemini/Copilot/Perplexity brand queries for "MarkOnMinds India".',
            'Content expansion: drop updated XLS/CSV in storage/imports/production → medca:import → post-sync runs automatically.',
            'Monthly: php artisan medca:post-launch-ops --activate-tracking',
            'Dashboards: /marketing/intelligence, /growth-center/ga4, Growth Center → SEO/GEO/AEO tabs.',
        ];
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private function toMarkdown(array $r): string
    {
        $scores = collect($r['health_audit']['scores'] ?? [])->map(fn ($v, $k) => "| {$k} | {$v} |")->implode("\n");
        $warnings = collect($r['health_audit']['warnings'] ?? [])->map(fn ($w) => "- {$w}")->implode("\n") ?: 'None';
        $manual = collect($r['manual_ops'] ?? [])->map(fn ($m) => "- {$m}")->implode("\n");
        $tracking = $r['tracking'] ?? [];
        $indexing = $r['indexing'] ?? [];
        $geo = $r['geo'] ?? [];
        $aeo = $r['aeo'] ?? [];
        $exec = $r['leads']['executive'] ?? [];
        $wa = $r['conversions']['whatsapp_clicks'] ?? [];
        $calls = $r['conversions']['call_clicks'] ?? [];

        return <<<MD
# MEDCA HEALTH CARE — Post-Launch Operations & Growth Report

**Generated:** {$r['generated_at']}
**Lookback:** {$r['period_days']} days
**Tracking activation run:** {$this->yn((bool) ($r['tracking_activation'] ?? false))}

> Uses existing systems only. No new engines. GSC index status requires manual Search Console review.

---

## 1. Tracking Activation

| Signal | Status |
|--------|--------|
| GTM configured | {$this->yn($tracking['gtm_configured'] ?? false)} |
| GA4 configured | {$this->yn($tracking['ga4_configured'] ?? false)} |
| Search Console verification | {$this->yn($tracking['search_console']['configured'] ?? false)} |
| WhatsApp tracking | {$this->yn($tracking['whatsapp_ready'] ?? false)} |
| Conversion events (config) | {$this->join($tracking['conversion_events'] ?? [])} |

**Client events:** `phone_click`, `whatsapp_click`, `form_start`, `form_submit`, `cta_click` via `tracking-events.blade.php` + `POST /marketing/track`.

**Activate from env:** set `MEDCA_GTM_CONTAINER_ID`, `MEDCA_GA4_MEASUREMENT_ID`, `MEDCA_GSC_VERIFICATION`, `MEDCA_WHATSAPP_NUMBER` then:

```bash
php artisan medca:post-launch-ops --activate-tracking
```

---

## 2. Indexing Monitoring (inventory)

| Page type | Active count |
|-----------|-------------:|
| Total active pages | {$indexing['active_pages_total']} |
| Category pages | {$indexing['category_pages']} |
| Service pages | {$indexing['service_pages']} |
| Sub-service pages | {$indexing['sub_service_pages']} |
| Indexable location pages | {$indexing['indexable_location_pages']} |
| Registry entries | {$indexing['registry_entries']} |

- Sitemap public: {$this->yn($indexing['sitemap_public'] ?? false)} — [{$indexing['sitemap_url']}]({$indexing['sitemap_url']})
- CMS roots live: {$this->join($indexing['cms_root_pages'] ?? [])}

{$indexing['gsc_note']}

---

## 3. SEO Monitoring

Growth readiness score components are in-app (Growth Center → Readiness).

**GA4 Data API (28d):**

MD
            .($r['ga4_api']['error'] ?? null
                ? "- API: **not connected** — {$r['ga4_api']['error']}"
                : '- Users: '.($r['ga4_api']['summary']['users'] ?? 0).', Sessions: '.($r['ga4_api']['summary']['sessions'] ?? 0).', Conversions: '.($r['ga4_api']['summary']['conversions'] ?? 0))
            .<<<MD


**Leads (period):** {$exec['total_leads']} total · top sources: {$this->formatTop($exec['top_sources'] ?? [])}

---

## 4. GEO Monitoring

| Metric | Value |
|--------|------:|
| Pin codes | {$geo['pin_codes_total']} |
| With landmarks | {$geo['with_landmarks']} |
| With hospitals | {$geo['with_hospitals']} |
| With coverage text | {$geo['with_coverage_text']} |
| Location pages | {$geo['location_pages']} |
| Indexable location pages | {$geo['indexable_location_pages']} |
| GEO readiness score | {$geo['readiness_score']}% |

CLI: `php artisan medca:geo-entity-report`

---

## 5. AEO Monitoring

| FAQ store | Count |
|-----------|------:|
| Service FAQs | {$aeo['faq_counts']['service_faqs']} |
| Category FAQs | {$aeo['faq_counts']['category_faqs']} |
| Sub-service FAQs | {$aeo['faq_counts']['sub_service_faqs']} |
| Pincode FAQs | {$aeo['faq_counts']['pincode_faqs']} |

Services with AI summary: {$aeo['services_with_ai_summary']} · Avg AEO score: {$aeo['aeo_scores_avg']} · Avg AI discovery score: {$aeo['ai_discovery_scores_avg']}

CLI: `php artisan medca:seo-hardening-report`

---

## 6. AI Discoverability

| Signal | Status |
|--------|--------|
| `/llm.txt` | active |
| `/ai-discovery` enabled | {$this->yn($r['ai_discoverability']['ai_discovery_enabled'] ?? false)} |
| Custom llm.txt | {$this->yn($r['ai_discoverability']['llm_txt_configured'] ?? false)} |

Validate entity graphs via go-live cert `schema` + `ai_discoverability` sections.

---

## 7. Lead Monitoring ({$r['period_days']}d)

| Metric | Value |
|--------|------:|
| Total leads | {$exec['total_leads']} |
| Qualified | {$exec['qualified_leads']} |
| Converted | {$exec['converted_leads']} |
| WhatsApp source leads | {$exec['whatsapp_leads']} |
| Call source leads | {$exec['call_leads']} |

Dashboard: `/marketing/intelligence` → Lead Intent + Attribution tabs.

---

## 8. Conversion Monitoring ({$r['period_days']}d)

| Channel | Month clicks |
|---------|-------------:|
| WhatsApp | {$wa['month']} |
| Phone | {$calls['month']} |
| Forms (intent) | {$r['conversions']['forms']} |

Conversion rate (leads): {$r['conversions']['pipeline']['conversion_rate']}%

---

## 9. Content Expansion (existing XLS workflow)

```bash
php artisan medca:import categories storage/imports/production/categories.csv
php artisan medca:import services storage/imports/production/services.xlsx
php artisan medca:import pincodes storage/imports/production/pincodes.xlsx
php artisan medca:import geo storage/imports/production/geo.xlsx
php artisan medca:populate-production   # full pipeline
```

**Current counts:** categories {$r['content_expansion']['counts']['categories']}, services {$r['content_expansion']['counts']['services']}, sub-services {$r['content_expansion']['counts']['sub_services']}, pincodes {$r['content_expansion']['counts']['pincodes']}

---

## 10. Monthly Health Audit

| Dimension | Score |
|-----------|------:|
{$scores}

**Decision:** {$r['health_audit']['decision']}

### Warnings
{$warnings}

---

## Manual Operations Checklist

{$manual}

---

## Quick Command Reference

| Task | Command |
|------|---------|
| This report | `medca:post-launch-ops` |
| Go-live cert | `medca:go-live-certification` |
| SEO/GEO/AEO hardening | `medca:seo-hardening-report` |
| GEO entities | `medca:geo-entity-report` |
| Bulk import | `medca:import {entity} {file}` |
| Full populate | `medca:populate-production` |

MD;
    }

    private function yn(bool $v): string
    {
        return $v ? 'YES' : 'NO';
    }

    /**
     * @param  mixed  $items
     */
    private function join(mixed $items): string
    {
        if (! is_array($items)) {
            return '—';
        }

        return $items === [] ? '—' : implode(', ', array_map('strval', $items));
    }

    /**
     * @param  array<string, int>|list<array{source?: string, total?: int}>  $top
     */
    private function formatTop(array $top): string
    {
        if ($top === []) {
            return '—';
        }

        $parts = [];
        foreach (array_slice($top, 0, 5) as $key => $value) {
            if (is_array($value)) {
                $parts[] = ($value['source'] ?? $key).':'.($value['total'] ?? 0);
            } else {
                $parts[] = "{$key}:{$value}";
            }
        }

        return implode(', ', $parts);
    }
}
