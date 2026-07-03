<?php

namespace App\Console\Commands;

use App\Services\Growth\BrokenLinkScannerService;
use Illuminate\Console\Command;

class ScanBrokenLinksCommand extends Command
{
    protected $signature = 'growth:scan-broken-links {--pages=150 : Max pages to scan}';

    protected $description = 'Phase B: scan internal links and store a broken link report';

    public function handle(BrokenLinkScannerService $scanner): int
    {
        $report = $scanner->runAndStore((int) $this->option('pages'));

        $this->info(sprintf(
            'Scan %s — pages: %d, links: %d, broken: %d',
            $report->status,
            $report->pages_scanned,
            $report->links_checked,
            $report->broken_count
        ));

        return self::SUCCESS;
    }
}
