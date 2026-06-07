<?php

use App\Models\ImportBatch;
use App\Models\PinCode;
use App\Models\User;
use App\ModuleAccess;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
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
        ->assertSee(__('Pin Codes'), false);
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
