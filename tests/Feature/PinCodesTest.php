<?php

use App\Livewire\Operations\PinCodes\Directory;
use App\Models\ImportBatch;
use App\Models\PinCode;
use App\Models\User;
use App\ModuleAccess;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function pinCodesTestWorkbook(string $path, array $sheets): void
{
    File::ensureDirectoryExists(dirname($path));
    $spreadsheet = new Spreadsheet;
    $spreadsheet->removeSheetByIndex(0);

    foreach ($sheets as $name => $rows) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($name);
        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 1], $value);
            }
        }
    }

    $spreadsheet->setActiveSheetIndex(0);
    (new Xlsx($spreadsheet))->save($path);
}

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

it('renders bulk selection controls on the pin codes directory', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);

    PinCode::factory()->count(2)->create(['city' => 'Bangalore']);

    $this->actingAs($user)
        ->get(route('operations.pin-codes.directory'))
        ->assertOk()
        ->assertSee(__('Select all'), false)
        ->assertSee('aria-label="'.__('Select row').'"', false);
});

it('bulk deletes a large pin code selection in one batch', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);

    PinCode::factory()->count(25)->create(['city' => 'Bangalore']);

    Livewire::actingAs($user)
        ->test(Directory::class)
        ->call('selectAllRows')
        ->call('openBulkAction', 'delete')
        ->set('bulkDeleteConfirmText', 'DELETE')
        ->call('confirmBulkAction')
        ->assertHasNoErrors();

    expect(PinCode::query()->count())->toBe(0);
});

it('bulk deletes all pin codes when select all is used', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);

    PinCode::factory()->count(3)->create(['city' => 'Bangalore']);

    Livewire::actingAs($user)
        ->test(Directory::class)
        ->call('selectAllRows')
        ->assertSet('bulkSelectAllFiltered', true)
        ->call('openBulkAction', 'delete')
        ->set('bulkDeleteConfirmText', 'DELETE')
        ->call('confirmBulkAction')
        ->assertHasNoErrors();

    expect(PinCode::query()->count())->toBe(0);
});

it('bulk deletes selected pin codes from the directory', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);

    $keep = PinCode::factory()->create(['pincode' => '560100', 'city' => 'Bangalore']);
    $remove = PinCode::factory()->create(['pincode' => '560101', 'city' => 'Bangalore']);

    Livewire::actingAs($user)
        ->test(Directory::class)
        ->call('toggleBulkRow', $remove->id)
        ->call('openBulkAction', 'delete')
        ->set('bulkDeleteConfirmText', 'DELETE')
        ->call('confirmBulkAction')
        ->assertHasNoErrors();

    expect(PinCode::query()->whereKey($keep->id)->exists())->toBeTrue()
        ->and(PinCode::query()->whereKey($remove->id)->exists())->toBeFalse()
        ->and(PinCode::withTrashed()->whereKey($remove->id)->exists())->toBeTrue()
        ->and(\App\Models\AdminDeletionTombstone::exists('pin_code', '560101'))->toBeTrue();
});

it('allows operations users to open pin codes overview', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);

    $this->actingAs($user)
        ->get(route('operations.pin-codes.overview'))
        ->assertOk()
        ->assertSee(__('Total pincodes'), false);
});

it('creates a pin code from the form', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
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

it('imports pincodes workbook after preview and confirm', function () {
    PinCode::factory()->create(['pincode' => '560001', 'area_name' => 'Existing', 'city' => 'Bangalore']);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);

    $path = storage_path('framework/testing/pincodes-ui.xlsx');
    pinCodesTestWorkbook($path, [
        'Pincodes' => [
            ['pincode', 'area_name', 'city'],
            ['560001', 'Duplicate', 'BLR'],
            ['560002', 'New Area', 'Bangalore'],
        ],
    ]);

    $this->actingAs($user);

    $preview = $this->from(route('operations.pin-codes.bulk-import'))
        ->post(route('operations.pin-codes.bulk-import.preview'), [
            'import_mode' => 'workbook',
            'workbook' => 'pincodes',
            'file' => new UploadedFile(
                $path,
                'pincodes.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            ),
        ]);
    $preview->assertRedirect(route('operations.pin-codes.bulk-import'))
        ->assertSessionHas('bulk_import_staging');

    $this->post(route('operations.pin-codes.bulk-import.confirm'))
        ->assertRedirect(route('operations.pin-codes.bulk-import'))
        ->assertSessionHas('import_result');

    expect((int) data_get(session('import_result'), 'created', 0))->toBeGreaterThanOrEqual(1);

    expect(PinCode::query()->where('pincode', '560002')->exists())->toBeTrue()
        ->and(PinCode::query()->where('pincode', '560001')->count())->toBe(1)
        ->and(ImportBatch::query()->where('entity_key', 'pincodes')->exists())->toBeTrue();
});

it('rejects workbook preview when required columns are missing', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);

    $path = storage_path('framework/testing/bad-pincodes.xlsx');
    pinCodesTestWorkbook($path, [
        'Pincodes' => [
            ['foo', 'bar'],
            ['1', '2'],
        ],
    ]);

    $this->actingAs($user)
        ->from(route('operations.pin-codes.bulk-import'))
        ->post(route('operations.pin-codes.bulk-import.preview'), [
            'import_mode' => 'workbook',
            'workbook' => 'pincodes',
            'file' => new UploadedFile(
                $path,
                'bad.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            ),
        ])
        ->assertRedirect(route('operations.pin-codes.bulk-import'))
        ->assertSessionHasErrors('file');
});
