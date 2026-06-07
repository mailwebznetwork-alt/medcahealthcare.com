<?php

namespace App\Console\Commands;

use App\Services\Launch\GoLiveCertificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GoLiveCertificationCommand extends Command
{
    protected $signature = 'medca:go-live-certification {--output= : Markdown report path}';

    protected $description = 'Phase 4 production audit and go-live certification';

    public function handle(GoLiveCertificationService $certification): int
    {
        $report = $certification->certify();
        $path = $this->option('output') ?: base_path('docs/GO-LIVE-CERTIFICATION.md');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $this->toMarkdown($report));

        $this->info("Go-live certification written to {$path}");
        $this->line('Decision: '.($report['decision'] ?? 'UNKNOWN'));
        $this->line('Certified: '.($report['certified'] ? 'YES' : 'NO'));

        $this->table(['Score', 'Value'], collect($report['scores'] ?? [])->map(fn ($v, $k) => [$k, $v])->values()->all());

        if (($report['critical_issues'] ?? []) !== []) {
            $this->error('Critical issues:');
            foreach ($report['critical_issues'] as $issue) {
                $this->line("  - {$issue}");
            }
        }

        foreach ($report['warnings'] ?? [] as $warning) {
            $this->warn($warning);
        }

        return ($report['certified'] ?? false) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private function toMarkdown(array $r): string
    {
        $scores = $r['scores'] ?? [];
        $checklist = collect($r['go_live_checklist'] ?? [])->map(fn ($c) => "| {$c['area']} | {$c['status']} |")->implode("\n");
        $critical = collect($r['critical_issues'] ?? [])->map(fn ($i) => "- {$i}")->implode("\n") ?: 'None';
        $warnings = collect($r['warnings'] ?? [])->map(fn ($w) => "- {$w}")->implode("\n") ?: 'None';
        $recs = collect($r['recommendations'] ?? [])->map(fn ($w) => "- {$w}")->implode("\n");

        $sections = '';
        foreach ($r['sections'] ?? [] as $key => $section) {
            if ($key === 'go_live_checklist') {
                continue;
            }
            $status = ($section['passed'] ?? false) ? 'PASS' : 'FAIL';
            $sections .= "\n### ".str_replace('_', ' ', ucfirst($key))." — {$status} ({$section['pass_rate']}%)\n";
            foreach ($section['failures'] ?? [] as $fail) {
                $sections .= "- FAIL: {$fail['name']} — {$fail['detail']}\n";
            }
        }

        return <<<MD
# MEDCA HEALTH CARE — Go-Live Certification (Phase 4)

**Generated:** {$r['generated_at']}
**Decision:** **{$r['decision']}**
**Certified:** {$this->yn($r['certified'] ?? false)}

## Scores

| Dimension | Score |
|-----------|------:|
| Architecture | {$scores['architecture']} |
| Data | {$scores['data']} |
| SEO | {$scores['seo']} |
| GEO | {$scores['geo']} |
| AEO | {$scores['aeo']} |
| Performance | {$scores['performance']} |
| Tracking | {$scores['tracking']} |
| Discovery | {$scores['discovery']} |
| **Launch** | **{$scores['launch']}** |

## Go-Live Checklist

| Area | Status |
|------|--------|
{$checklist}

## Critical Issues

{$critical}

## Warnings

{$warnings}

## Recommendations

{$recs}

## Section Details
{$sections}

MD;
    }

    private function yn(bool $v): string
    {
        return $v ? 'YES' : 'NO';
    }
}
