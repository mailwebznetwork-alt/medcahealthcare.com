<?php

namespace App\Services\Import;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

/**
 * Reads CSV, XLS, and XLSX into normalized row arrays.
 */
final class SpreadsheetReader
{
    /**
     * @return list<string>
     */
    public function sheetNames(mixed $source): array
    {
        $path = $this->resolvePath($source);
        if ($path === null) {
            throw new \InvalidArgumentException(__('Could not read the uploaded file.'));
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($extension, ['xls', 'xlsx'], true)) {
            return [];
        }

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);

            return $reader->listWorksheetNames($path);
        } catch (ReaderException $e) {
            throw new \RuntimeException(__('Could not list workbook sheets: :msg', ['msg' => $e->getMessage()]));
        }
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string|null>>, total_data_rows: int, format: string, sheet?: string}
     */
    public function read(mixed $source, ?string $sheetName = null): array
    {
        $path = $this->resolvePath($source);
        if ($path === null) {
            throw new \InvalidArgumentException(__('Could not read the uploaded file.'));
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv', 'txt' => $this->readCsv($path),
            'xls', 'xlsx' => $this->readSpreadsheet($path, $extension, $sheetName),
            default => $this->readCsv($path),
        };
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string|null>>, total_data_rows: int, format: string}
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException(__('Could not open the CSV file.'));
        }

        $first = fgets($handle);
        if ($first === false) {
            fclose($handle);

            throw new \RuntimeException(__('The file is empty.'));
        }

        if (str_starts_with($first, "\xEF\xBB\xBF")) {
            $first = substr($first, 3);
        }

        $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), str_getcsv($first));
        $rows = [];
        $total = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }
            $total++;
            $rows[] = $this->padRow($row, count($headers));
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total_data_rows' => $total,
            'format' => 'csv',
        ];
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string|null>>, total_data_rows: int, format: string}
     */
    private function readSpreadsheet(string $path, string $extension, ?string $sheetName = null): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
            $sheet = $sheetName !== null
                ? $spreadsheet->getSheetByName($sheetName)
                : $spreadsheet->getActiveSheet();
            if ($sheet === null) {
                throw new \RuntimeException(__('Worksheet not found: :name', ['name' => $sheetName ?? '']));
            }
            $matrix = $sheet->toArray(null, true, true, false);
        } catch (ReaderException $e) {
            throw new \RuntimeException(__('Could not parse spreadsheet: :msg', ['msg' => $e->getMessage()]));
        }

        if ($matrix === []) {
            throw new \RuntimeException(__('The spreadsheet is empty.'));
        }

        $rawHeaders = array_shift($matrix);
        $headers = array_map(fn ($h) => $this->normalizeHeader((string) ($h ?? '')), $rawHeaders ?? []);
        $rows = [];
        $total = 0;

        foreach ($matrix as $row) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }
            $total++;
            $rows[] = $this->padRow(array_map(fn ($v) => $v === null ? null : (string) $v, $row), count($headers));
        }

        $result = [
            'headers' => $headers,
            'rows' => $rows,
            'total_data_rows' => $total,
            'format' => $extension,
        ];

        if ($sheetName !== null) {
            $result['sheet'] = $sheetName;
        }

        return $result;
    }

    /**
     * @param  list<string>  $headers
     * @return array<string, string|null>
     */
    public function mapRow(array $headers, array $row): array
    {
        $mapped = [];
        foreach ($headers as $i => $header) {
            if ($header === '') {
                continue;
            }
            $mapped[$header] = isset($row[$i]) ? $this->normalizeCell($row[$i]) : null;
        }

        return $mapped;
    }

    public function normalizeHeader(string $header): string
    {
        $header = trim($header);
        $header = strtolower($header);
        $header = str_replace([' ', '-'], '_', $header);

        return $header;
    }

    private function normalizeCell(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim((string) $value);

        return $str === '' ? null : $str;
    }

    /**
     * @param  list<mixed>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<mixed>  $row
     * @return list<string|null>
     */
    private function padRow(array $row, int $length): array
    {
        $padded = [];
        for ($i = 0; $i < $length; $i++) {
            $padded[] = isset($row[$i]) ? $this->normalizeCell($row[$i]) : null;
        }

        return $padded;
    }

    private function resolvePath(mixed $source): ?string
    {
        if ($source instanceof UploadedFile) {
            return $source->getRealPath() ?: null;
        }

        if (is_string($source) && is_readable($source)) {
            return $source;
        }

        return null;
    }
}
