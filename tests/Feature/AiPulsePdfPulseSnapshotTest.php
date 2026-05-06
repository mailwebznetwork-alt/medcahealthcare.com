<?php

use App\Services\Growth\AiPulseService;
use Illuminate\Support\Facades\Cache;

it('includes pdf_pulse pillars when rebuilding the ai pulse snapshot', function () {
    Cache::forget('markonminds:ai_pulse:snapshot:v1');

    $service = app(AiPulseService::class);
    $service->rebuildSnapshotCache(true);

    $snap = Cache::get('markonminds:ai_pulse:snapshot:v1');

    expect($snap)->toBeArray()
        ->and($snap)->toHaveKey('pdf_pulse')
        ->and($snap['pdf_pulse'])->toHaveKeys([
            'business_health',
            'predictive_insights',
            'conversion_insights',
            'visibility_geo_aeo',
            'source',
        ]);
});
