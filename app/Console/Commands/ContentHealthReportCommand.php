<?php

namespace App\Console\Commands;

use App\Services\MasterSpec\ContentHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ContentHealthReportCommand extends Command
{
    protected $signature = 'medca:content-health-report {--output= : Markdown output path}';

    protected $description = 'Report thin content, missing FAQ/schema, pending medical review';

    public function handle(ContentHealthService $health): int
    {
        $data = $health->report();

        $markdown = "# MarkOnMinds Content Health Report\n\n";
        $markdown .= 'Generated: '.now()->timezone('Asia/Kolkata')->toDateTimeString().' IST'."\n\n";

        foreach ($data as $key => $value) {
            if ($key === 'recommendations') {
                continue;
            }
            $markdown .= '- **'.str_replace('_', ' ', $key).'**: '.$value."\n";
        }

        $markdown .= "\n## Recommendations\n\n";
        foreach ($data['recommendations'] as $line) {
            $markdown .= '- '.$line."\n";
        }

        $output = $this->option('output') ?: base_path('docs/CONTENT-HEALTH-REPORT.md');
        File::ensureDirectoryExists(dirname($output));
        File::put($output, $markdown);

        $this->info("Content health report written to {$output}");

        return self::SUCCESS;
    }
}
