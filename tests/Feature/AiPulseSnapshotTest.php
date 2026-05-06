<?php

use App\Services\Growth\AiPulseService;
use Illuminate\Support\Facades\Cache;

it('includes pulse narrative pillars when rebuilding the ai pulse snapshot', function () {
    Cache::forget('markonminds:ai_pulse:snapshot:v1');

    $service = app(AiPulseService::class);
    $service->rebuildSnapshotCache(true);

    $snap = Cache::get('markonminds:ai_pulse:snapshot:v1');

    expect($snap)->toBeArray()
        ->and($snap)->toHaveKey('pulse_narrative')
        ->and($snap)->toHaveKey('speed_detail')
        ->and($snap['speed_detail'])->toHaveKeys(['source', 'score_0_100'])
        ->and($snap['scores'])->toHaveKeys(['speed', 'rankmath', 'aio', 'brand_authority'])
        ->and($snap['pulse_narrative'])->toHaveKeys([
            'business_health',
            'predictive_insights',
            'conversion_insights',
            'visibility_geo_aeo',
            'source',
        ]);
});

it('rebuilds the snapshot synchronously when the cache is cold', function () {
    Cache::forget('markonminds:ai_pulse:snapshot:v1');

    $snap = app(AiPulseService::class)->cachedSnapshotOrDispatch(false);

    expect($snap)->toBeArray()
        ->and($snap['scan_in_progress'] ?? true)->toBeFalse()
        ->and($snap['scores'])->toHaveKey('aio');
});
