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
            'pincode' => ['required', 'string', 'max:20'],
            'serviceable' => ['required', 'boolean'],
            'landing_page' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'in:high,medium,low'],
        ];
    }
}
