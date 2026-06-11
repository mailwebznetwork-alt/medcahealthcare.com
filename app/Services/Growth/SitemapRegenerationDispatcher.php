<?php

namespace App\Services\Growth;

use App\Jobs\Growth\RegenerateSitemapJob;

class SitemapRegenerationDispatcher
{
    public function dispatch(): void
    {
        if (! config('sitemap.queue_enabled', true) || ! config('sitemap.cache_enabled', true)) {
            return;
        }

        RegenerateSitemapJob::dispatch()->afterCommit();
    }
}
