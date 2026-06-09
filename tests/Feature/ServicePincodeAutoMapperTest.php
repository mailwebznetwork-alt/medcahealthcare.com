<?php

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\PinCode;
use App\Models\Service;
use App\Services\Import\ImportPostSyncService;
use App\Services\Import\WorkbookImportOrchestrator;
use App\Services\Operations\ServicePincodeAutoMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

function writeAutoMapWorkbook(string $path): void
{
    File::ensureDirectoryExists(dirname($path));
    $spreadsheet = new Spreadsheet;
    $spreadsheet->removeSheetByIndex(0);

    foreach ([
        'Pincodes' => [
            ['pincode', 'area_name', 'city', 'is_serviceable', 'is_active'],
            ['560076', 'Arekere', 'Bangalore', 'TRUE', 'TRUE'],
        ],
        'GeoEnrichment' => [
            ['pincode', 'coverage_text'],
            ['560076', '24x7 care'],
        ],
        'Mappings' => [
            ['service_code', 'pincode'],
            ['SRV-BLR-01', '560076'],
        ],
    ] as $name => $rows) {
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

it('auto maps eligible pincodes to published services', function () {
    $pin = PinCode::factory()->create([
        'pincode' => '560076',
        'city' => 'Bangalore',
        'is_active' => true,
        'is_serviceable' => true,
    ]);

    $service = Service::factory()->create([
        'service_code' => 'elder-care',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    $result = app(ServicePincodeAutoMapper::class)->map();

    expect($result['mapped'])->toBeTrue()
        ->and($result['services_processed'])->toBe(1)
        ->and($service->fresh()->pincodes->pluck('id')->all())->toContain($pin->id);
});

it('runs auto map once after pincodes import post sync when services exist', function () {
    PinCode::factory()->create([
        'pincode' => '560076',
        'city' => 'Bangalore',
        'is_active' => true,
        'is_serviceable' => true,
    ]);

    Service::factory()->create([
        'service_code' => 'elder-care',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    $ran = app(ImportPostSyncService::class)->syncForEntity('pincodes');

    expect($ran)->toContain('service_pincode_auto_map');
});

it('skips manual mappings sheet in pincodes workbook preview when auto map is enabled', function () {
    $path = storage_path('framework/testing/auto-map-preview.xlsx');
    writeAutoMapWorkbook($path);

    $upload = UploadedFile::fake()->createWithContent('pincodes.xlsx', file_get_contents($path));
    $preview = app(WorkbookImportOrchestrator::class)->preview('pincodes', $upload);

    $mappingSheet = collect($preview['sheets'])->firstWhere('sheet_key', 'mappings');

    expect($preview['valid'])->toBeTrue()
        ->and($mappingSheet)->not->toBeNull()
        ->and($mappingSheet['rows'][0]['status'] ?? null)->toBe('system_auto')
        ->and(collect($mappingSheet['rows'])->pluck('status'))->not->toContain('invalid');
});
