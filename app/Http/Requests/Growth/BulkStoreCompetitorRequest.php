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
            'competitors' => ['required', 'array', 'min:1', 'max:100'],
            'competitors.*.name' => ['required', 'string', 'max:255', 'unique:competitors,name'],
            'competitors.*.website' => ['nullable', 'url', 'max:255'],
            'competitors.*.is_active' => ['nullable', 'boolean'],
            'competitors.*.is_intercept_target' => ['nullable', 'boolean'],
        ];
    }
}
