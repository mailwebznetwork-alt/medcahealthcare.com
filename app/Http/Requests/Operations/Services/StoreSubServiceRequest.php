<?php

namespace App\Http\Requests\Operations\Services;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Service;
use App\Models\SubService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $service = $this->route('service');

        return $service instanceof Service && $this->user()?->can('update', $service) === true;
    }

    protected function prepareForValidation(): void
    {
        $code = strtolower(trim((string) $this->input('sub_service_code', '')));
        $code = str_replace([' ', '_'], '-', $code);
        if ($code !== '') {
            $this->merge(['sub_service_code' => $code]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Service $service */
        $service = $this->route('service');

        return [
            'sub_service_code' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('sub_services', 'sub_service_code')->where('service_id', $service->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'short_summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_top_rated' => ['nullable', 'boolean'],
            'show_on_homepage' => ['nullable', 'boolean'],
            'show_on_about' => ['nullable', 'boolean'],
            'show_on_contact' => ['nullable', 'boolean'],
            'publish_status' => ['required', Rule::enum(PublishStatus::class)],
            'visibility' => ['required', Rule::enum(ServiceVisibility::class)],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:500'],
            'seo.h1' => ['nullable', 'string', 'max:255'],
            'seo.focus_keywords' => ['nullable', 'string', 'max:500'],
            'faqs' => ['nullable', 'array'],
            'faqs.*.question' => ['nullable', 'string', 'max:500'],
            'faqs.*.answer' => ['nullable', 'string'],
        ];
    }
}
