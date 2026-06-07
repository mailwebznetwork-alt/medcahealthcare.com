<?php

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Models\PinCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

it('has production import files', function () {
    $path = config('medca_launch.imports_path');

    expect(File::exists("{$path}/categories.csv"))->toBeTrue()
        ->and(File::exists("{$path}/services.csv"))->toBeTrue()
        ->and(File::exists("{$path}/sub_services.csv"))->toBeTrue()
        ->and(File::exists("{$path}/pincodes.csv"))->toBeTrue()
        ->and(File::exists("{$path}/geo.csv"))->toBeTrue();
});

it('populates production catalog end to end', function () {
    $this->artisan('medca:populate-production', ['--skip-media-seeder' => true])
        ->assertSuccessful();

    expect(ServiceCategory::count())->toBeGreaterThanOrEqual(7)
        ->and(Service::count())->toBe(7)
        ->and(SubService::count())->toBe(4)
        ->and(PinCode::query()->where('is_serviceable', true)->count())->toBeGreaterThanOrEqual(15)
        ->and(Service::query()->where('service_code', 'medical-lab')->exists())->toBeTrue()
        ->and(SubService::query()->where('sub_service_code', 'blood-test')->exists())->toBeTrue();
});

it('generates production launch report with scores', function () {
    $this->artisan('medca:populate-production', ['--skip-media-seeder' => true])
        ->assertSuccessful();

    $this->artisan('medca:production-launch-report')
        ->assertSuccessful();

    expect(File::exists(base_path('docs/PRODUCTION-LAUNCH-REPORT.md')))->toBeTrue();
});
