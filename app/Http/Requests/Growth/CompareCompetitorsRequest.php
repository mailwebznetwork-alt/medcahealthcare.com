<?php

namespace App\Http\Requests\Growth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompareCompetitorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'competitor_ids' => ['required', 'array', 'min:2', 'max:10'],
            'competitor_ids.*' => ['required', 'integer', Rule::exists('competitors', 'id')],
        ];
    }
}
