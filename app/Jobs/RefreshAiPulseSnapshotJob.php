<?php

namespace App\Jobs;

use App\Services\Growth\AiPulseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshAiPulseSnapshotJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public bool $force = false) {}

    public function handle(AiPulseService $service): void
    {
        $service->rebuildSnapshotCache($this->force);
    }
}
