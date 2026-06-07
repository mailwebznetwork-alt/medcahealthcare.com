<?php

namespace App\Services\Import;

/**
 * In-memory service row defaults loaded from ServiceDefaults workbook sheet.
 */
final class ServiceImportDefaults
{
    /** @var array<string, string|null> */
    private array $global = [];

    /** @var array<string, array<string, string|null>> */
    private array $byServiceCode = [];

    public function clear(): void
    {
        $this->global = [];
        $this->byServiceCode = [];
    }

    /**
     * @param  array<string, string|null>  $row
     */
    public function setGlobal(array $row): void
    {
        $this->global = array_merge($this->global, $this->filterDefaults($row));
    }

    /**
     * @param  array<string, string|null>  $row
     */
    public function setForService(string $serviceCode, array $row): void
    {
        $code = trim($serviceCode);
        if ($code === '') {
            return;
        }

        $this->byServiceCode[$code] = array_merge(
            $this->byServiceCode[$code] ?? [],
            $this->filterDefaults($row)
        );
    }

    /**
     * @param  array<string, string|null>  $row
     * @return array<string, string|null>
     */
    public function mergeForService(array $row): array
    {
        $code = trim((string) ($row['service_code'] ?? ''));
        $defaults = $this->global;
        if ($code !== '' && isset($this->byServiceCode[$code])) {
            $defaults = array_merge($defaults, $this->byServiceCode[$code]);
        }

        return ImportSupport::mergeRowDefaults($row, $defaults);
    }

    /**
     * @param  array<string, string|null>  $row
     * @return array<string, string|null>
     */
    private function filterDefaults(array $row): array
    {
        return array_filter(
            $row,
            static fn ($value, string $key): bool => $key !== 'service_code' && $value !== null && $value !== '',
            ARRAY_FILTER_USE_BOTH
        );
    }
}
