<?php

namespace App\Jobs\Operations;

use App\Models\Service;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshPeerServiceInternalLinksJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(
        public int $serviceId,
    ) {}

    public function handle(): void
    {
        $service = Service::query()->with('categories')->find($this->serviceId);
        if ($service === null) {
            return;
        }

        $categoryIds = $service->categories->pluck('id')->all();

        $peerQuery = Service::query()->whereKeyNot($service->id);
        if ($categoryIds !== []) {
            $peerQuery->inCategories($categoryIds);
        }

        $peerQuery->pluck('id')->each(function (int $peerId): void {
            RefreshServiceInternalLinksJob::dispatch($peerId)->onQueue($this->queue ?? 'default');
        });
    }
}
