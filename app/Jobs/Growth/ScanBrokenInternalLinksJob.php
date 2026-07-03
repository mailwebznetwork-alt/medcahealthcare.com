<?php

namespace App\Jobs\Growth;

use App\Services\Growth\BrokenLinkScannerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScanBrokenInternalLinksJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 900;

    public function __construct(
        public int $maxPages = 150,
    ) {}

    public function handle(BrokenLinkScannerService $scanner): void
    {
        $scanner->runAndStore($this->maxPages);
    }
}
