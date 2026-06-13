<?php

namespace App\Console\Commands;

use App\Services\MasterSpec\SemanticKeywordClusterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SemanticKeywordClusterCommand extends Command
{
    protected $signature = 'medca:semantic-keyword-clusters {--output= : Markdown path} {--limit=50 : Max clusters}';

    protected $description = 'Cluster services by shared semantic keyword tokens';

    public function handle(SemanticKeywordClusterService $clusterer): int
    {
        $clusters = $clusterer->clusters((int) $this->option('limit'));

        $markdown = "# Semantic Keyword Clusters\n\n";
        $markdown .= 'Generated: '.now()->timezone('Asia/Kolkata')->toDateTimeString().' IST'."\n\n";

        foreach ($clusters as $cluster) {
            $markdown .= "## {$cluster['cluster']} ({$cluster['count']} services)\n\n";
            foreach ($cluster['services'] as $code) {
                $markdown .= "- `{$code}`\n";
            }
            $markdown .= "\n";
        }

        $output = $this->option('output') ?: base_path('docs/SEMANTIC-KEYWORD-CLUSTERS.md');
        File::ensureDirectoryExists(dirname($output));
        File::put($output, $markdown);

        $this->info("Written {$output} (".count($clusters).' clusters)');

        return self::SUCCESS;
    }
}
