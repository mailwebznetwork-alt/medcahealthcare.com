<?php

namespace App\Http\Requests\Operations\Services;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('schema_json') === '') {
            $this->merge(['schema_json' => null]);
        }
    }

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
            'short_summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'price_range' => ['nullable', 'string', 'max:120'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'target_keywords' => ['nullable', 'array'],
            'target_keywords.*' => ['string', 'max:120'],
            'ai_keywords' => ['nullable', 'array'],
            'ai_keywords.*' => ['string', 'max:120'],
            'quality_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'publish_status' => ['required', Rule::enum(PublishStatus::class)],
            'visibility' => ['required', Rule::enum(ServiceVisibility::class)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'detail_page_id' => ['nullable', 'integer', 'exists:pages,id'],
            'featured_image' => ['nullable', 'image', 'max:5120'],
            'icon' => ['nullable', 'image', 'max:2048'],
            'gallery_files' => ['nullable', 'array'],
            'gallery_files.*' => ['image', 'max:5120'],
            'clear_featured_image' => ['boolean'],
            'clear_icon' => ['boolean'],

            'seo' => ['nullable', 'array'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:65535'],
            'seo.focus_keywords' => ['nullable', 'array'],
            'seo.focus_keywords.*' => ['string', 'max:120'],
            'seo.h1' => ['nullable', 'string', 'max:255'],
            'seo.h2' => ['nullable', 'array'],
            'seo.h2.*' => ['string', 'max:500'],
            'seo.h3' => ['nullable', 'array'],
            'seo.h3.*' => ['string', 'max:500'],
            'seo.ai_context' => ['nullable', 'string'],
            'seo.search_intent' => ['nullable', 'string', 'max:255'],

            'faqs' => ['nullable', 'array'],
            'faqs.*.question' => ['nullable', 'string', 'max:2000'],
            'faqs.*.answer' => ['nullable', 'string'],

            'schema_type' => ['nullable', 'string', 'max:120'],
            'schema_json' => ['nullable', 'json'],

            'pincodes' => ['nullable', 'array'],
            'pincodes.*' => ['integer', 'exists:pin_codes,id'],
        ];
    }
}
