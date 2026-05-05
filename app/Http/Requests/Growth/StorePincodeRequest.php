<?php

namespace App\Http\Requests\Growth;

use Illuminate\Foundation\Http\FormRequest;

class StorePincodeRequest extends FormRequest
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
            'geo_location_id' => ['nullable', 'integer', 'exists:geo_locations,id'],
            'code' => ['required', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
