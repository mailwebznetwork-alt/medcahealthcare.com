<?php

namespace App\Http\Requests\Operations\Services;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $code = strtolower(trim((string) $this->input('service_code', '')));
        $code = str_replace([' ', '_'], '-', $code);
        if ($code !== '') {
            $this->merge(['service_code' => $code]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', Service::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'service_code' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z][a-zA-Z0-9_-]*$/', 'unique:services,service_code'],
            'price_range' => ['nullable', 'string', 'max:120'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'publish_status' => ['required', Rule::enum(PublishStatus::class)],
            'visibility' => ['required', Rule::enum(ServiceVisibility::class)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'detail_page_id' => ['nullable', 'integer', 'exists:pages,id'],
            'pincodes' => ['nullable', 'array'],
            'pincodes.*' => ['integer', 'exists:pin_codes,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'service_code' => __('service code'),
        ];
    }
}
