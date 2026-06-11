<?php

use App\Models\PinCode;
use App\Services\Import\WorkbookImportOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

function writePincodeTestWorkbook(string $path, array $pincodesRows, array $geoRows = []): void
{
    File::ensureDirectoryExists(dirname($path));
    $spreadsheet = new Spreadsheet;
    $spreadsheet->removeSheetByIndex(0);

    $pinSheet = $spreadsheet->createSheet();
    $pinSheet->setTitle('Pincodes');
    foreach ($pincodesRows as $rowIndex => $row) {
        foreach ($row as $colIndex => $value) {
            $pinSheet->setCellValue([$colIndex + 1, $rowIndex + 1], $value);
        }
    }

    if ($geoRows !== []) {
        $geoSheet = $spreadsheet->createSheet();
        $geoSheet->setTitle('GeoEnrichment');
        foreach ($geoRows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $geoSheet->setCellValue([$colIndex + 1, $rowIndex + 1], $value);
            }
        }
    }

    $spreadsheet->setActiveSheetIndex(0);
    (new Xlsx($spreadsheet))->save($path);
}

it('workbook pincodes import updates existing rows in production environment', function () {
    app()->detectEnvironment(fn (): string => 'production');

    PinCode::factory()->create([
        'pincode' => '560076',
        'area_name' => 'Old Area',
        'city' => 'Bangalore',
    ]);

    $path = storage_path('framework/testing/pincodes-prod-upsert.xlsx');
    writePincodeTestWorkbook($path, [
        ['pincode', 'area_name', 'city'],
        ['560076', 'Arekere', 'Bangalore'],
    ]);

    $result = app(WorkbookImportOrchestrator::class)->commit('pincodes', $path, null, 'pincodes.xlsx', false);

    expect($result['updated'])->toBe(1)
        ->and($result['skipped'])->toBe(0)
        ->and($result['failed'])->toBe(0)
        ->and(PinCode::query()->where('pincode', '560076')->value('area_name'))->toBe('Arekere');
});

it('workbook pincodes import collapses duplicate rows and updates each unique pin once', function () {
    app()->detectEnvironment(fn (): string => 'production');

    PinCode::factory()->create([
        'pincode' => '560076',
        'area_name' => 'Old Area',
        'city' => 'Bangalore',
    ]);

    $path = storage_path('framework/testing/pincodes-dedupe.xlsx');
    writePincodeTestWorkbook($path, [
        ['pincode', 'area_name', 'city'],
        ['560076', 'First Row', 'Bangalore'],
        ['560076', 'Second Row', 'Bangalore'],
        ['560076', 'Final Area', 'Bangalore'],
    ]);

    $orchestrator = app(WorkbookImportOrchestrator::class);
    $preview = $orchestrator->preview('pincodes', $path);
    $pinSheet = collect($preview['sheets'])->firstWhere('entity', 'pincodes');

    expect($pinSheet['import_summary']['total_rows'] ?? null)->toBe(3)
        ->and($pinSheet['import_summary']['unique_pincodes'] ?? null)->toBe(1)
        ->and($pinSheet['import_summary']['duplicate_rows'] ?? null)->toBe(2)
        ->and($pinSheet['import_summary']['would_update'] ?? null)->toBe(1);

    $result = $orchestrator->commit('pincodes', $path, null, 'pincodes.xlsx', false);

    expect($result['updated'])->toBe(1)
        ->and($result['skipped'])->toBe(0)
        ->and(PinCode::query()->where('pincode', '560076')->value('area_name'))->toBe('Final Area');
});

it('workbook preview marks existing pincodes as update not duplicate', function () {
    app()->detectEnvironment(fn (): string => 'production');

    PinCode::factory()->create([
        'pincode' => '560076',
        'area_name' => 'Existing',
        'city' => 'Bangalore',
    ]);

    $path = storage_path('framework/testing/pincodes-preview-upsert.xlsx');
    writePincodeTestWorkbook($path, [
        ['pincode', 'area_name', 'city'],
        ['560076', 'Updated Area', 'Bangalore'],
    ]);

    $preview = app(WorkbookImportOrchestrator::class)->preview('pincodes', $path);
    $pinSheet = collect($preview['sheets'])->firstWhere('entity', 'pincodes');

    expect($pinSheet['rows'][0]['status'] ?? null)->toBe('update')
        ->and($pinSheet['import_summary']['would_update'] ?? null)->toBe(1)
        ->and($pinSheet['import_summary']['would_skip'] ?? null)->toBe(0);
});
