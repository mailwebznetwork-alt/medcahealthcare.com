<?php

namespace App\Console\Commands;

use App\Services\MasterSpec\EntityGraphAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class EntityGraphAuditCommand extends Command
{
    protected $signature = 'medca:entity-graph-audit {--output= : Markdown output path}';

    protected $description = 'Audit digital growth platform entity graph: orphans, weak links, missing relationships';

    public function handle(EntityGraphAuditService $audit): int
    {
        $data = $audit->audit();

        $markdown = "# MarkOnMinds Entity Graph Audit\n\n";
        $markdown .= 'Generated: '.now()->timezone('Asia/Kolkata')->toDateTimeString().' IST'."\n\n";
        $markdown .= "## Summary\n\n";
        $markdown .= "| Metric | Count |\n|--------|-------|\n";

        foreach ($data as $key => $value) {
            if (is_int($value)) {
                $markdown .= '| '.str_replace('_', ' ', $key).' | '.$value." |\n";
            }
        }

        $markdown .= "\n## Weak relationships (sample)\n\n";
        foreach ($data['weak_relationships'] as $row) {
            $markdown .= '- `'.($row['code'] ?? $row['type'] ?? 'unknown').'`: '.($row['issue'] ?? '')."\n";
        }

        $markdown .= "\n## Missing relationships (sample)\n\n";
        foreach ($data['missing_relationships'] as $row) {
            $markdown .= '- '.json_encode($row)."\n";
        }

        $output = $this->option('output') ?: base_path('docs/ENTITY-GRAPH-AUDIT.md');
        File::ensureDirectoryExists(dirname($output));
        File::put($output, $markdown);

        $this->info("Entity graph audit written to {$output}");

        return self::SUCCESS;
    }
}
