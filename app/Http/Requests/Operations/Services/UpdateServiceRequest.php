<?php

namespace App\Http\Requests\Operations\Services;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Service $service */
        $service = $this->route('service');

        return $this->user()?->can('update', $service) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Service $service */
        $service = $this->route('service');

        return [
            'title' => ['required', 'string', 'max:255'],
            'service_code' => ['required', 'string', Rule::in([$service->service_code])],
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
}
