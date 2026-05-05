<?php

namespace App\Http\Requests\Growth;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreCompetitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'competitors' => ['required', 'array', 'max:100'],
            'competitors.*.name' => ['required', 'string', 'max:255', 'distinct'],
            'competitors.*.website' => ['nullable', 'url', 'max:2048'],
            'competitors.*.is_active' => ['sometimes', 'boolean'],
            'competitors.*.is_intercept_target' => ['sometimes', 'boolean'],
        ];
    }
}
