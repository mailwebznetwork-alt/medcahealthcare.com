<?php

namespace App\Jobs\Growth;

use App\Services\Growth\SeoSitemapFileGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RegenerateSitemapJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function handle(SeoSitemapFileGenerator $generator): void
    {
        if (! config('sitemap.cache_enabled', true)) {
            return;
        }

        try {
            $generator->regenerateAll();
        } catch (\Throwable $e) {
            Log::error('Sitemap regeneration failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}
