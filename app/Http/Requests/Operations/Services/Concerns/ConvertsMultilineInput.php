<?php

namespace App\Http\Requests\Operations\Services\Concerns;

trait ConvertsMultilineInput
{
    /**
     * @return list<string>|null
     */
    protected function linesToListingArray(mixed $value): ?array
    {
        $items = $this->linesToStringList($value);

        return $items === [] ? null : $items;
    }

    /**
     * @return list<string>
     */
    protected function linesToStringList(mixed $value): array
    {
        if (! is_string($value)) {
            return [];
        }

        $lines = preg_split("/\r\n|\r|\n/", $value) ?: [];

        return array_values(array_filter(array_map(
            static fn (string $line): string => trim($line),
            $lines
        ), static fn (string $line): bool => $line !== ''));
    }
}
