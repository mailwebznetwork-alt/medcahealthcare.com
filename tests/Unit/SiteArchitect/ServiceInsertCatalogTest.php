<?php

use App\Models\Service;
use App\Services\SiteArchitect\ServiceInsertCatalog;

it('returns every service for the architect insert dropdown', function () {
    $active = Service::factory()->create(['service_code' => 'catalog-active']);
    $inactive = Service::factory()->create([
        'service_code' => 'catalog-inactive',
        'is_active' => false,
    ]);

    $codes = app(ServiceInsertCatalog::class)
        ->forDropdown()
        ->pluck('service_code')
        ->all();

    expect($codes)
        ->toContain($active->service_code, $inactive->service_code);
});

it('resolves tokens for inactive services by code', function () {
    Service::factory()->create([
        'service_code' => 'catalog-token-inactive',
        'is_active' => false,
    ]);

    $catalog = app(ServiceInsertCatalog::class);

    expect($catalog->existsForToken('catalog-token-inactive'))->toBeTrue()
        ->and($catalog->existsForToken('missing-code'))->toBeFalse()
        ->and($catalog->existsForToken(''))->toBeFalse();
});

it('prepends a services grid layout when block code only contains tokens', function () {
    $catalog = app(ServiceInsertCatalog::class);

    $code = $catalog->ensureLayoutInBlockCode("{{service:alpha}}\n{{service:beta}}");

    expect($code)
        ->toContain('@foreach ($services as $service)')
        ->toContain('{{service:alpha}}')
        ->toContain('{{service:beta}}');
});
