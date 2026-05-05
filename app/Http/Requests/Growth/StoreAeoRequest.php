<?php

namespace App\Http\Requests\Growth;

use Illuminate\Foundation\Http\FormRequest;

class StoreAeoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ai_crawl_enabled' => ['required', 'boolean'],
            'llm_visibility_score' => ['required', 'integer', 'min:0', 'max:100'],
            'entity_consistency_score' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }
}
