<?php

namespace App\Jobs\Operations;

use App\Models\Service;
use App\Services\Operations\ServiceInternalLinkingEngine;
use App\Services\Operations\ServiceOptimizationScorer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshServiceInternalLinksJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $serviceId,
    ) {}

    public function handle(
        ServiceInternalLinkingEngine $linkingEngine,
        ServiceOptimizationScorer $scorer,
    ): void {
        $service = Service::query()->find($this->serviceId);
        if ($service === null) {
            return;
        }

        $linkingEngine->persist($service->fresh(['pincodes', 'locationPages.page', 'categories']));
        $scorer->scoreAndPersist($service->fresh());
    }
}
