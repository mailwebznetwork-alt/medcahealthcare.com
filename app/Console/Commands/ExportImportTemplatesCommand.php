<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportImportTemplatesCommand extends Command
{
    protected $signature = 'medca:export-import-templates {--path=storage/imports/templates}';

    protected $description = 'Generate blank services.xlsx and pincodes.xlsx master import templates';

    public function handle(): int
    {
        $dir = base_path($this->option('path'));
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            $this->error("Could not create directory: {$dir}");

            return self::FAILURE;
        }

        $columns = config('import_registry.template_columns', []);

        $this->writeWorkbook($dir.'/services.xlsx', [
            'Categories' => $columns['categories'] ?? [],
            'Services' => $columns['services'] ?? [],
            'SubServices' => $columns['sub_services'] ?? [],
            'ServiceDefaults' => $columns['service_defaults'] ?? [],
        ]);

        $this->writeWorkbook($dir.'/pincodes.xlsx', [
            'Pincodes' => $columns['pincodes'] ?? [],
            'GeoEnrichment' => $columns['geo_enrichment'] ?? [],
            'Mappings' => $columns['mappings'] ?? [],
        ]);

        $this->info("Templates written to {$dir}");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, list<string>>  $sheets
     */
    private function writeWorkbook(string $path, array $sheets): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        foreach ($sheets as $name => $headers) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($name);
            foreach ($headers as $col => $header) {
                $sheet->setCellValue([$col + 1, 1], $header);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);
        (new Xlsx($spreadsheet))->save($path);
    }
}
