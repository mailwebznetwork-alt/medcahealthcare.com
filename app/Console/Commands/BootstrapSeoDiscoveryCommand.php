<?php

namespace App\Console\Commands;

use App\Services\Growth\BrokenLinkScannerService;
use App\Services\Growth\SeoDiscoveryBootstrapService;
use Illuminate\Console\Command;

class BootstrapSeoDiscoveryCommand extends Command
{
    protected $signature = 'growth:bootstrap-discovery {--no-ping : Skip GSC/IndexNow pings}';

    protected $description = 'Phase A: bootstrap SEO discovery (IndexNow key, AI crawl, GSC sitemap, profile links)';

    public function handle(SeoDiscoveryBootstrapService $bootstrap): int
    {
        $result = $bootstrap->run(pingSearchEngines: ! $this->option('no-ping'));

        $this->info('SEO discovery bootstrap complete.');
        $this->table(
            ['Check', 'Value'],
            collect($result)
                ->except('messages')
                ->map(fn (mixed $value, string $key): array => [$key, is_bool($value) ? ($value ? 'yes' : 'no') : (string) $value])
                ->values()
                ->all()
        );

        foreach ($result['messages'] as $message) {
            $this->line(' - '.$message);
        }

        $status = $bootstrap->status();
        if (filled($status['indexnow_key_url'])) {
            $this->line('IndexNow key URL: '.$status['indexnow_key_url']);
        }

        return self::SUCCESS;
    }
}
