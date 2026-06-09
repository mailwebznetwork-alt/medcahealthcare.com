<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportImportTemplatesCommand extends Command
{
    protected $signature = 'medca:export-import-templates {--path=storage/imports/templates}';

    protected $description = 'Generate services.xlsx and pincodes.xlsx master import templates';

    public function handle(): int
    {
        $dir = base_path($this->option('path'));
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            $this->error("Could not create directory: {$dir}");

            return self::FAILURE;
        }

        $columns = config('import_registry.template_columns', []);
        $samples = config('import_registry.template_sample_rows', []);

        $this->writeWorkbook($dir.'/services.xlsx', [
            'Categories' => [
                'headers' => $columns['categories'] ?? [],
                'samples' => $samples['categories'] ?? [],
            ],
            'Services' => [
                'headers' => $columns['services'] ?? [],
                'samples' => $samples['services'] ?? [],
            ],
            'SubServices' => [
                'headers' => $columns['sub_services'] ?? [],
                'samples' => $samples['sub_services'] ?? [],
            ],
            'ServiceDefaults' => [
                'headers' => $columns['service_defaults'] ?? [],
                'samples' => [],
            ],
        ]);

        $this->writeWorkbook($dir.'/pincodes.xlsx', [
            'Pincodes' => [
                'headers' => $columns['pincodes'] ?? [],
                'samples' => [],
            ],
            'GeoEnrichment' => [
                'headers' => $columns['geo_enrichment'] ?? [],
                'samples' => [],
            ],
            'Mappings' => [
                'headers' => $columns['mappings'] ?? [],
                'samples' => [],
            ],
        ]);

        $this->info("Templates written to {$dir}");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array{headers: list<string>, samples: list<array<string, string>>}>  $sheets
     */
    private function writeWorkbook(string $path, array $sheets): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        foreach ($sheets as $name => $sheetConfig) {
            $headers = $sheetConfig['headers'];
            $samples = $sheetConfig['samples'];

            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($name);

            foreach ($headers as $col => $header) {
                $sheet->setCellValue([$col + 1, 1], $header);
            }

            $headerIndex = array_flip($headers);
            foreach ($samples as $rowOffset => $sampleRow) {
                $rowNumber = $rowOffset + 2;
                foreach ($sampleRow as $column => $value) {
                    if (! isset($headerIndex[$column])) {
                        continue;
                    }
                    $sheet->setCellValue([$headerIndex[$column] + 1, $rowNumber], $value);
                }
            }
        }

        $spreadsheet->setActiveSheetIndex(0);
        (new Xlsx($spreadsheet))->save($path);
    }
}
