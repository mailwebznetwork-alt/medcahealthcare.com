<?php

namespace App\Http\Requests\Growth;

use App\Models\Competitor;
use Illuminate\Foundation\Http\FormRequest;

class StoreInterceptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Competitor::class) ?? false;
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
            'keyword' => ['required', 'string', 'max:255'],
            'gap_type' => ['required', 'string', 'max:120'],
            'action' => ['required', 'string'],
            'priority' => ['required', 'in:high,medium,low'],
            'status' => ['nullable', 'in:pending,in_progress,completed'],
        ];
    }
}
