<?php

namespace App\Http\Requests\Operations\Services\Concerns;

trait NormalizesServiceListingLines
{
    use ConvertsMultilineInput;

    protected function normalizeServiceListingLines(): void
    {
        $this->merge([
            'procedures' => $this->linesToListingArray($this->input('procedures_lines')),
            'specialized_care' => $this->linesToListingArray($this->input('specialized_care_lines')),
            'shifts' => $this->linesToListingArray($this->input('shifts_lines')),
        ]);
    }
}
