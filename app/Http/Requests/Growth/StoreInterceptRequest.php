<?php

namespace App\Http\Requests\Growth;

use Illuminate\Foundation\Http\FormRequest;

class StoreInterceptRequest extends FormRequest
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
            'competitor_id' => ['nullable', 'integer', 'exists:competitors,id'],
            'title' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'string', 'max:40'],
            'priority' => ['required', 'integer', 'min:1', 'max:3'],
            'status' => ['nullable', 'in:active,completed,paused'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
