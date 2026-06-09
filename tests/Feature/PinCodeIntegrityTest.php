<?php

use App\Models\AdminDeletionTombstone;
use App\Models\PinCode;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Governance\PinCodeCreationGuard;
use App\Services\Growth\GeoService;
use App\Services\Import\GeoEnrichmentEntityImporter;
use App\Services\Import\PinCodeSpreadsheetImporter;
use App\Services\Operations\PinCodeDeletionService;
use Database\Seeders\MedcaBangalorePinCodesSeeder;
use Illuminate\Support\Facades\Artisan;

function pinCodeIntegrityUser(): User
{
    return User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);
}

it('soft deletes pincode and records tombstone on admin delete', function () {
    $user = pinCodeIntegrityUser();
    $pin = PinCode::factory()->create(['pincode' => '560999', 'city' => 'Bangalore']);

    $this->actingAs($user);
    app(PinCodeDeletionService::class)->delete($pin, 'ui');

    expect(PinCode::query()->where('pincode', '560999')->exists())->toBeFalse()
        ->and(PinCode::withTrashed()->where('pincode', '560999')->exists())->toBeTrue()
        ->and(AdminDeletionTombstone::exists('pin_code', '560999'))->toBeTrue();
});

it('blocks seeder from recreating tombstoned pincode', function () {
    $user = pinCodeIntegrityUser();
    $pin = PinCode::factory()->create(['pincode' => '560999', 'area_name' => 'Test Area', 'city' => 'Bangalore']);

    $this->actingAs($user);
    app(PinCodeDeletionService::class)->delete($pin, 'ui');

    (new MedcaBangalorePinCodesSeeder)->run();

    expect(PinCode::query()->where('pincode', '560999')->exists())->toBeFalse();
});

it('blocks growth center from recreating tombstoned pincode', function () {
    $user = pinCodeIntegrityUser();
    $pin = PinCode::factory()->create(['pincode' => '560999', 'city' => 'Bangalore']);

    $this->actingAs($user);
    app(PinCodeDeletionService::class)->delete($pin, 'ui');

    $result = app(GeoService::class)->addPincode([
        'pincode' => '560999',
        'area_name' => 'Blocked Area',
        'city' => 'Bangalore',
        'serviceable' => true,
    ]);

    expect($result)->toBeNull()
        ->and(PinCode::query()->where('pincode', '560999')->exists())->toBeFalse();
});

it('allows spreadsheet import to restore tombstoned pincode', function () {
    $user = pinCodeIntegrityUser();
    $pin = PinCode::factory()->create(['pincode' => '560999', 'area_name' => 'Test', 'city' => 'Bangalore']);

    $this->actingAs($user);
    app(PinCodeDeletionService::class)->delete($pin, 'ui');

    $importer = app(PinCodeSpreadsheetImporter::class);
    $result = $importer->importParsed([
        'headers' => ['pincode', 'area_name', 'city'],
        'rows' => [['560999', 'Reimported', 'Bangalore']],
    ]);

    $restored = PinCode::query()->where('pincode', '560999')->first();

    expect($result['updated'] ?? 0)->toBe(1)
        ->and($restored)->not->toBeNull()
        ->and($restored->area_name)->toBe('Reimported')
        ->and(AdminDeletionTombstone::exists('pin_code', '560999'))->toBeFalse();
});

it('allows geo enrichment import to restore tombstoned pincode', function () {
    $user = pinCodeIntegrityUser();
    $pin = PinCode::factory()->create(['pincode' => '560999', 'city' => 'Bangalore']);

    $this->actingAs($user);
    app(PinCodeDeletionService::class)->delete($pin, 'ui');

    $importer = app(GeoEnrichmentEntityImporter::class);
    $result = $importer->importParsed([
        'headers' => ['pincode', 'area_name', 'city'],
        'rows' => [['560999', 'Geo Area', 'Bangalore']],
    ]);

    expect($result['updated'] ?? 0)->toBe(1)
        ->and(PinCode::query()->where('pincode', '560999')->exists())->toBeTrue()
        ->and(AdminDeletionTombstone::exists('pin_code', '560999'))->toBeFalse();
});

it('allows explicit ui recreation of tombstoned pincode', function () {
    AdminDeletionTombstone::record('pin_code', '560999', null, 'ui', 'deleted');

    expect(app(PinCodeCreationGuard::class)->canCreatePincode('560999', 'ui'))->toBeTrue()
        ->and(app(PinCodeCreationGuard::class)->canCreatePincode('560999', 'import'))->toBeTrue()
        ->and(app(PinCodeCreationGuard::class)->canCreatePincode('560999', 'seeder'))->toBeFalse();
});

it('blocks populate command when master data protection is enabled', function () {
    config(['master_data_protection.enabled' => true]);

    $result = app(\App\Services\Launch\ProductionPopulationService::class)->populate(false);

    expect($result['blocked'] ?? false)->toBeTrue();
});

it('pincode creation guard blocks tombstoned pincode', function () {
    AdminDeletionTombstone::record('pin_code', '560999', null, 'test', 'spec');

    expect(app(PinCodeCreationGuard::class)->canCreatePincode('560999', 'seeder'))->toBeFalse();
});

it('preserves active pincodes after unrelated tombstone delete', function () {
    $active = PinCode::factory()->create(['pincode' => '560100', 'city' => 'Bangalore']);
    $remove = PinCode::factory()->create(['pincode' => '560999', 'city' => 'Bangalore']);

    $this->actingAs(pinCodeIntegrityUser());
    app(PinCodeDeletionService::class)->delete($remove, 'ui');

    expect(PinCode::query()->whereKey($active->id)->exists())->toBeTrue();
});
