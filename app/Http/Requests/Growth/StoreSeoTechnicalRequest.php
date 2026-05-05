<?php

namespace App\Http\Requests\Growth;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeoTechnicalRequest extends FormRequest
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
            'robots_txt' => ['nullable', 'string'],
            'sitemap_enabled' => ['required', 'boolean'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'indexable' => ['required', 'boolean'],
            'llm_txt' => ['nullable', 'string'],
            'ai_discovery_enabled' => ['required', 'boolean'],
            'google_site_verification' => ['nullable', 'string', 'max:255'],
        ];
    }
}
