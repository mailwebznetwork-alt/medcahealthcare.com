<?php

namespace App\Services\Import;

/**
 * Pending entity keys from earlier sheets in the same master workbook (preview only).
 */
final class WorkbookImportContext
{
    /** @var array<string, true> */
    private array $pendingServiceCodes = [];

    public function clear(): void
    {
        $this->pendingServiceCodes = [];
    }

    public function addPendingServiceCode(string $code): void
    {
        $code = trim($code);
        if ($code === '') {
            return;
        }

        $this->pendingServiceCodes[$code] = true;
    }

    public function hasPendingServiceCode(string $code): bool
    {
        return isset($this->pendingServiceCodes[trim($code)]);
    }

    /**
     * @param  array{headers: list<string>, rows: list<list<string|null>>}  $parsed
     */
    public function absorbServiceCodesFromParsed(array $parsed, SpreadsheetReader $reader): void
    {
        foreach ($parsed['rows'] as $rawRow) {
            $mapped = $reader->mapRow($parsed['headers'], $rawRow);
            $code = trim((string) ($mapped['service_code'] ?? ''));
            if ($code !== '') {
                $this->addPendingServiceCode($code);
            }
        }
    }
}
