<?php

namespace App\Console\Commands;

use App\Models\BangaloreZone;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\Import\ImportSupport;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportCatalogCommand extends Command
{
    protected $signature = 'medca:export-catalog
                            {--path=storage/exports : Output directory}
                            {--workbook=all : services, pincodes, or all}';

    protected $description = 'Export live catalog database to XLSX workbooks (Master Spec data management)';

    public function handle(): int
    {
        $dir = base_path($this->option('path'));
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            $this->error("Could not create {$dir}");

            return self::FAILURE;
        }

        $workbook = $this->option('workbook');
        $timestamp = now()->timezone('Asia/Kolkata')->format('Y-m-d_His');

        if (in_array($workbook, ['services', 'all'], true)) {
            $this->exportServicesWorkbook($dir.'/catalog-services-'.$timestamp.'.xlsx');
        }

        if (in_array($workbook, ['pincodes', 'all'], true)) {
            $this->exportPincodesWorkbook($dir.'/catalog-pincodes-'.$timestamp.'.xlsx');
        }

        $this->info("Catalog export complete in {$dir}");

        return self::SUCCESS;
    }

    private function exportServicesWorkbook(string $path): void
    {
        $columns = config('import_registry.template_columns', []);
        $categoryCodeMap = ServiceCategory::query()->pluck('code', 'id');

        $categories = ServiceCategory::query()->with('seo', 'parent')->orderBy('code')->get()->map(function (ServiceCategory $c): array {
            return $this->rowFromColumns($columns['categories'] ?? [], [
                'code' => $c->code,
                'name' => $c->name,
                'slug' => $c->slug,
                'description' => $c->description,
                'short_summary' => $c->short_summary,
                'parent_code' => $c->parent?->code,
                'sort_order' => $c->sort_order,
                'is_active' => $c->is_active ? 'TRUE' : 'FALSE',
                'is_featured' => $c->is_featured ? 'TRUE' : 'FALSE',
                'publish_status' => $c->publish_status?->value ?? $c->publish_status,
                'visibility' => $c->visibility?->value ?? $c->visibility,
                'key_benefits' => $this->lines($c->key_benefits),
                'eligibility' => $this->lines($c->eligibility),
                'process_steps' => $this->lines($c->process_steps),
                'ai_summary' => $c->ai_summary,
                'quick_answer' => $c->quick_answer,
                'why_medca' => $c->why_medca,
                'key_takeaways' => $this->lines($c->key_takeaways),
                'activities_included' => $this->lines($c->activities_included),
                'medical_review_status' => $c->medical_review_status?->value ?? $c->medical_review_status,
                'verification_status' => $c->verification_status?->value ?? $c->verification_status,
                'meta_title' => $c->seo?->meta_title,
                'meta_description' => $c->seo?->meta_description,
                'line_icon' => $c->line_icon,
            ]);
        })->all();

        $services = Service::query()->with(['seo', 'categories'])->orderBy('service_code')->get()->map(function (Service $s): array {
            $columns = config('import_registry.template_columns.services', []);

            return $this->rowFromColumns($columns, [
                'primary_category_code' => $s->categories->first()?->code,
                'category_codes' => $s->categories->pluck('code')->implode('|'),
                'service_code' => $s->service_code,
                'title' => $s->title,
                'short_summary' => $s->short_summary,
                'description' => $s->description,
                'key_benefits' => $this->lines($s->key_benefits),
                'eligibility' => $this->lines($s->eligibility),
                'process_steps' => $this->lines($s->process_steps),
                'ai_summary' => $s->ai_summary,
                'quick_answer' => $s->quick_answer,
                'why_medca' => $s->why_medca,
                'key_takeaways' => $this->lines($s->key_takeaways),
                'activities_included' => $this->lines($s->activities_included),
                'medical_review_status' => $s->medical_review_status?->value ?? $s->medical_review_status,
                'verification_status' => $s->verification_status?->value ?? $s->verification_status,
                'featured_video_url' => $s->featured_video_url,
                'is_active' => $s->is_active ? 'TRUE' : 'FALSE',
                'publish_status' => $s->publish_status?->value ?? $s->publish_status,
                'visibility' => $s->visibility?->value ?? $s->visibility,
                'meta_title' => $s->seo?->meta_title,
                'meta_description' => $s->seo?->meta_description,
                'search_intent' => $s->seo?->search_intent,
                'line_icon' => $s->line_icon,
                'target_keywords' => $this->lines($s->target_keywords),
                'ai_keywords' => $this->lines($s->ai_keywords),
            ]);
        })->all();

        $subServices = SubService::query()->with(['seo', 'service'])->orderBy('sub_service_code')->get()->map(function (SubService $sub): array {
            $columns = config('import_registry.template_columns.sub_services', []);

            return $this->rowFromColumns($columns, [
                'parent_service_code' => $sub->service?->service_code,
                'sub_service_code' => $sub->sub_service_code,
                'title' => $sub->title,
                'short_summary' => $sub->short_summary,
                'description' => $sub->description,
                'key_benefits' => $this->lines($sub->key_benefits),
                'ai_summary' => $sub->ai_summary,
                'quick_answer' => $sub->quick_answer,
                'why_medca' => $sub->why_medca,
                'medical_review_status' => $sub->medical_review_status?->value ?? $sub->medical_review_status,
                'is_active' => $sub->is_active ? 'TRUE' : 'FALSE',
                'publish_status' => $sub->publish_status?->value ?? $sub->publish_status,
                'visibility' => $sub->visibility?->value ?? $sub->visibility,
                'meta_title' => $sub->seo?->meta_title,
                'line_icon' => $sub->line_icon,
            ]);
        })->all();

        $this->writeWorkbook($path, [
            'Categories' => ['headers' => $columns['categories'] ?? [], 'rows' => $categories],
            'Services' => ['headers' => $columns['services'] ?? [], 'rows' => $services],
            'SubServices' => ['headers' => $columns['sub_services'] ?? [], 'rows' => $subServices],
        ]);

        $this->line("  services workbook: {$path}");
    }

    private function exportPincodesWorkbook(string $path): void
    {
        $columns = config('import_registry.template_columns', []);

        $pincodes = PinCode::query()->with('bangaloreZone')->orderBy('pincode')->get()->map(function (PinCode $pin): array {
            return $this->rowFromColumns($columns['pincodes'] ?? [], [
                'pincode' => $pin->pincode,
                'area_name' => $pin->area_name,
                'city' => $pin->city,
                'state' => $pin->state,
                'locality' => $pin->locality,
                'bangalore_zone_code' => $pin->bangaloreZone?->code,
                'is_serviceable' => $pin->is_serviceable ? 'TRUE' : 'FALSE',
                'is_active' => $pin->is_active ? 'TRUE' : 'FALSE',
                'priority' => $pin->priority,
                'meta_title' => $pin->meta_title,
                'meta_description' => $pin->meta_description,
            ]);
        })->all();

        $mappings = [];
        Service::query()->with('pincodes')->orderBy('service_code')->each(function (Service $service) use (&$mappings, $columns): void {
            foreach ($service->pincodes as $pin) {
                $mappings[] = $this->rowFromColumns($columns['mappings'] ?? [], [
                    'service_code' => $service->service_code,
                    'pincode' => $pin->pincode,
                    'priority' => $pin->pivot->priority ?? null,
                    'is_visible' => ($pin->pivot->is_visible ?? true) ? 'TRUE' : 'FALSE',
                    'is_featured' => ($pin->pivot->is_featured ?? false) ? 'TRUE' : 'FALSE',
                    'coverage_notes' => $pin->pivot->coverage_notes ?? null,
                ]);
            }
        });

        $this->writeWorkbook($path, [
            'Pincodes' => ['headers' => array_merge($columns['pincodes'] ?? [], ['bangalore_zone_code']), 'rows' => $pincodes],
            'Mappings' => ['headers' => $columns['mappings'] ?? [], 'rows' => $mappings],
        ]);

        $this->line("  pincodes workbook: {$path}");
    }

    /**
     * @param  list<string>  $headers
     * @param  array<string, mixed>  $data
     * @return array<string, string|null>
     */
    private function rowFromColumns(array $headers, array $data): array
    {
        $row = [];
        foreach ($headers as $header) {
            $value = $data[$header] ?? null;
            $row[$header] = is_scalar($value) || $value === null ? (string) ($value ?? '') : json_encode($value);
        }

        foreach ($data as $key => $value) {
            if (! isset($row[$key]) && $value !== null && $value !== '') {
                $row[$key] = is_scalar($value) ? (string) $value : json_encode($value);
            }
        }

        return $row;
    }

  /**
     * @param  array<int, string>|null  $items
     */
    private function lines(?array $items): ?string
    {
        if ($items === null || $items === []) {
            return null;
        }

        return implode('|', ImportSupport::normalizeLineArray($items));
    }

    /**
     * @param  array<string, array{headers: list<string>, rows: list<array<string, string|null>>}>  $sheets
     */
    private function writeWorkbook(string $path, array $sheets): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        foreach ($sheets as $name => $config) {
            $headers = $config['headers'];
            $rows = $config['rows'];
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($name);

            foreach ($headers as $col => $header) {
                $sheet->setCellValue([$col + 1, 1], $header);
            }

            $headerIndex = array_flip($headers);
            foreach ($rows as $offset => $row) {
                $rowNumber = $offset + 2;
                foreach ($row as $column => $value) {
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
