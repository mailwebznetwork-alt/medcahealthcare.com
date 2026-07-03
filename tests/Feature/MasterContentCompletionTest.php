<?php

use App\Models\BusinessProfile;
use App\Models\SeoEntity;
use App\Models\Service;
use App\Support\BrandContentCatalog;

it('runs master content completion and fills global seo entity', function () {
    BusinessProfile::query()->firstOrCreate(
        ['website' => config('app.url')],
        ['name' => 'Medca Healthcare']
    );

    $service = Service::factory()->create([
        'title' => 'Test Nursing Care',
        'short_summary' => null,
        'why_medca' => null,
        'is_active' => true,
    ]);

    $this->artisan('medca:complete-master-content', ['--no-schema-sync' => true])
        ->assertSuccessful();

    expect(SeoEntity::query()->whereNotNull('organization_name')->exists())->toBeTrue();
    expect($service->fresh()->short_summary)->not->toBeNull();
    expect($service->fresh()->why_medca)->not->toBeNull();
    expect(BrandContentCatalog::get('brand_name'))->toContain('Medca');
});
