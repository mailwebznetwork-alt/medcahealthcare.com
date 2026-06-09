<?php

namespace App\Support;

use App\Services\Growth\AiPulseService;
use App\Services\Growth\ContentSeoAutoFillService;

use function Illuminate\Support\defer;

/**
 * Defer heavy Growth / AI Pulse work until after the HTTP response so Site Architect saves stay fast.
 */
final class PostPublishGrowthSync
{
    public static function defer(): void
    {
        defer(function (): void {
            app(ContentSeoAutoFillService::class)->refreshAggregateSignals();
            app(AiPulseService::class)->triggerAuditAfterPublish();
            GrowthReadinessReport::forget();
        });
    }
}
