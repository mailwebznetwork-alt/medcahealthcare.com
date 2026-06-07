<?php

use App\Services\Launch\GoLiveCertificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

it('runs go-live certification against populated data', function () {
    $this->artisan('medca:populate-production', ['--skip-media-seeder' => true])
        ->assertSuccessful();

    $report = app(GoLiveCertificationService::class)->certify();

    expect($report)->toHaveKeys(['decision', 'scores', 'sections', 'certified'])
        ->and($report['sections'])->toHaveKeys([
            'import_system', 'categories', 'services', 'discovery', 'change_pincode',
            'page_generation', 'matrix', 'seo', 'geo', 'aeo', 'schema',
        ])
        ->and($report['scores']['launch'])->toBeGreaterThan(0);
});

it('generates go-live certification report via command', function () {
    $this->artisan('medca:populate-production', ['--skip-media-seeder' => true])
        ->assertSuccessful();

    $this->artisan('medca:go-live-certification')
        ->assertSuccessful();

    expect(File::exists(base_path('docs/GO-LIVE-CERTIFICATION.md')))->toBeTrue();
});

it('certifies change pincode and discovery engines', function () {
    $this->artisan('medca:populate-production', ['--skip-media-seeder' => true])
        ->assertSuccessful();

    $report = app(GoLiveCertificationService::class)->certify();

    expect($report['sections']['discovery']['passed'])->toBeTrue()
        ->and($report['sections']['change_pincode']['checks_passed'])->toBeGreaterThanOrEqual(4);
});
