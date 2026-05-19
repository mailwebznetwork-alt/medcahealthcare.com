<?php

namespace App\Http\Requests\Operations\Services\Concerns;

trait NormalizesServiceListingLines
{
    protected function normalizeServiceListingLines(): void
    {
        $this->merge([
            'procedures' => $this->linesToListingArray($this->input('procedures_lines')),
            'specialized_care' => $this->linesToListingArray($this->input('specialized_care_lines')),
            'shifts' => $this->linesToListingArray($this->input('shifts_lines')),
        ]);
    }

    /**
     * @return list<string>|null
     */
    private function linesToListingArray(mixed $value): ?array
    {
        if (! is_string($value)) {
            return null;
        }

        $lines = preg_split("/\r\n|\r|\n/", $value) ?: [];
        $items = array_values(array_filter(array_map(
            static fn (string $line): string => trim($line),
            $lines
        ), static fn (string $line): bool => $line !== ''));

        return $items === [] ? null : $items;
    }
}
