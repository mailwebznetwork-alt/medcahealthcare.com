<?php

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\ImportBatch;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\Import\WorkbookImportOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

function writeTestWorkbook(string $path, array $sheets): void
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

it('previews and imports services master workbook', function () {
    $path = storage_path('framework/testing/services.xlsx');
    writeTestWorkbook($path, [
        'Categories' => [
            ['code', 'name'],
            ['lab-care', 'Lab Care'],
        ],
        'Services' => [
            ['service_code', 'title', 'category_codes'],
            ['blood-test', 'Blood Test', 'lab-care'],
        ],
        'SubServices' => [
            ['parent_service_code', 'sub_service_code', 'title'],
            ['blood-test', 'cbc', 'CBC Panel'],
        ],
    ]);

    $orchestrator = app(WorkbookImportOrchestrator::class);
    $preview = $orchestrator->preview('services', $path);

    expect($preview['valid'])->toBeTrue()
        ->and($preview['sheets'])->toHaveCount(3);

    $subSheet = collect($preview['sheets'])->firstWhere('entity', 'sub_services');
    expect($subSheet['rows'][0]['status'] ?? null)->toBe('ready')
        ->and($subSheet['rows'][0]['key'] ?? null)->toBe('blood-test/cbc');

    $result = $orchestrator->commit('services', $path, null, 'services.xlsx', false);

    expect($result['created'])->toBeGreaterThanOrEqual(3)
        ->and(ServiceCategory::query()->where('code', 'lab-care')->exists())->toBeTrue()
        ->and(Service::query()->where('service_code', 'blood-test')->exists())->toBeTrue()
        ->and(SubService::query()->where('sub_service_code', 'cbc')->exists())->toBeTrue()
        ->and(ImportBatch::query()->count())->toBeGreaterThanOrEqual(3);
});

it('imports pincodes master workbook and auto maps services after post sync', function () {
    Service::factory()->create([
        'service_code' => 'nursing',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    $path = storage_path('framework/testing/pincodes.xlsx');
    writeTestWorkbook($path, [
        'Pincodes' => [
            ['pincode', 'area_name', 'city', 'is_serviceable', 'is_active'],
            ['560076', 'Arekere', 'Bangalore', 'TRUE', 'TRUE'],
        ],
        'GeoEnrichment' => [
            ['pincode', 'coverage_text', 'landmark_names'],
            ['560076', '24x7 nursing', 'Temple|Park'],
        ],
        'Mappings' => [
            ['service_code', 'pincode', 'priority'],
            ['nursing', '560076', '5'],
        ],
    ]);

    $orchestrator = app(WorkbookImportOrchestrator::class);
    $result = $orchestrator->commit('pincodes', $path, null, 'pincodes.xlsx', true);

    $pin = PinCode::query()->where('pincode', '560076')->first();

    expect($result['failed'])->toBe(0)
        ->and($pin)->not->toBeNull()
        ->and($pin->landmarks()->count())->toBeGreaterThan(0)
        ->and($result['post_sync'])->toContain('service_pincode_auto_map')
        ->and(Service::query()->where('service_code', 'nursing')->first()->pincodes)->toHaveCount(1);
});

it('previews uploaded pincodes workbook when temp path has no extension', function () {
    $path = storage_path('framework/testing/upload-pincodes.xlsx');
    writeTestWorkbook($path, [
        'Pincodes' => [
            ['pincode', 'area_name', 'city'],
            ['560076', 'Arekere', 'Bangalore'],
        ],
    ]);

    $upload = UploadedFile::fake()->createWithContent('pincodes.xlsx', file_get_contents($path));
    $preview = app(WorkbookImportOrchestrator::class)->preview('pincodes', $upload);

    expect($preview['valid'])->toBeTrue()
        ->and($preview['sheets'])->not->toBeEmpty()
        ->and($preview['errors'])->toBeEmpty();
});

it('detects workbook key from filename', function () {
    $orchestrator = app(WorkbookImportOrchestrator::class);

    expect($orchestrator->detectWorkbookKey('services.xlsx'))->toBe('services')
        ->and($orchestrator->detectWorkbookKey('pincodes.xlsx'))->toBe('pincodes')
        ->and($orchestrator->detectWorkbookKey('categories.csv'))->toBeNull();
});
