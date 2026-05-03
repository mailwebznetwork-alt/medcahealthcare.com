<?php

use App\Models\PinCode;
use App\Models\PinCodeImportLog;
use App\Models\User;
use App\ModuleAccess;
use Illuminate\Http\UploadedFile;

it('forbids pin codes directory when the user lacks operations access', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::DASHBOARD])
            ->all(),
    ]);

    $this->actingAs($user)
        ->get(route('operations.pin-codes.directory'))
        ->assertForbidden();
});

it('allows operations users to open pin codes overview', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);

    $this->actingAs($user)
        ->get(route('operations.pin-codes.overview'))
        ->assertOk()
        ->assertSee(__('Pin Codes'), false);
});

it('creates a pin code from the form', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);

    $this->actingAs($user)
        ->post(route('operations.pin-codes.store'), [
            'pincode' => '560076',
            'area_name' => 'Arekere',
            'city' => 'Bangalore',
            'locality' => 'Bannerghatta Road',
            'is_serviceable' => '1',
            'is_active' => '1',
            'delivery_charge' => '49.00',
            'meta_title' => 'Arekere 560076',
            'meta_description' => 'Service area',
            'seo_keywords' => 'arekere, bangalore',
            'slug' => '',
            'geo_page_ready' => '0',
        ])
        ->assertRedirect(route('operations.pin-codes.directory'));

    $row = PinCode::query()->where('pincode', '560076')->first();
    expect($row)->not->toBeNull()
        ->and($row->is_serviceable)->toBeTrue()
        ->and($row->slug)->not->toBeNull();
});

it('imports CSV after preview and confirm', function () {
    PinCode::factory()->create(['pincode' => '560001', 'area_name' => 'Existing', 'city' => 'Bangalore']);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);

    $csv = "pincode,area_name,city,locality,serviceability,delivery_charge\n560001,Duplicate,BLR,,1,\n560002,New Area,Bangalore,JP Nagar,1,25.50\n";

    $this->actingAs($user);

    $this->post(route('operations.pin-codes.bulk-import.preview'), [
        'file' => UploadedFile::fake()->createWithContent('pins.csv', $csv),
    ])
        ->assertRedirect(route('operations.pin-codes.bulk-import'));

    $this->post(route('operations.pin-codes.bulk-import.confirm'), [
        'confirm_import' => '1',
    ])
        ->assertRedirect(route('operations.pin-codes.overview'));

    expect(PinCode::query()->where('pincode', '560002')->exists())->toBeTrue()
        ->and(PinCode::query()->where('pincode', '560001')->count())->toBe(1)
        ->and(PinCodeImportLog::query()->count())->toBe(1);
});

it('rejects CSV preview when required columns are missing', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);

    $csv = "foo,bar\n1,2\n";

    $this->actingAs($user)
        ->post(route('operations.pin-codes.bulk-import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('bad.csv', $csv),
        ])
        ->assertRedirect(route('operations.pin-codes.bulk-import'))
        ->assertSessionHasErrors('file');
});
