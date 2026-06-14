<?php

use App\Models\Service;
use App\Services\Import\ImportPostSyncService;
use App\Services\Operations\ServiceLocationPageProvisioner;
use App\Services\Operations\ServiceLocationTemplateResolver;
use App\Models\PinCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('runs services sync master after services import post sync', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('services:sync-master')
        ->andReturn(0);
    Artisan::shouldReceive('call')
        ->once()
        ->with('medca:sync-page-registry')
        ->andReturn(0);
    Artisan::shouldReceive('call')
        ->once()
        ->with('medca:fill-quick-answers')
        ->andReturn(0);

    $ran = app(ImportPostSyncService::class)->syncForEntity('services');

    expect($ran)->toContain('services:sync-master', 'medca:sync-page-registry', 'medca:fill-quick-answers', 'content_seo_aggregate_refresh', 'sitemap_regeneration_dispatched');
});

it('applies workbook location h1 template when provisioning', function () {
    $service = Service::factory()->create([
        'service_code' => 'tpl-svc',
        'title' => 'Template Service',
        'custom_fields' => ['location_h1_template' => 'CUSTOM {service} @ {area}'],
    ]);
    $pin = PinCode::factory()->create(['pincode' => '560111', 'area_name' => 'Test Area', 'city' => 'Bangalore']);

    $title = app(ServiceLocationTemplateResolver::class)->locationTitle($service, $pin);

    expect($title)->toBe('CUSTOM Template Service @ Test Area');
});

it('exports full services template column count', function () {
    Artisan::call('medca:export-import-templates');

    $expected = count(config('import_registry.template_columns.services', []));
    expect($expected)->toBeGreaterThan(50)
        ->and(file_exists(storage_path('imports/templates/services.xlsx')))->toBeTrue();
});
