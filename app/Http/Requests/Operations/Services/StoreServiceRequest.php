<?php

namespace App\Http\Requests\Operations\Services;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Http\Requests\Operations\Services\Concerns\NormalizesServiceKeywordArrays;
use App\Http\Requests\Operations\Services\Concerns\NormalizesServiceListingLines;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    use NormalizesServiceKeywordArrays;
    use NormalizesServiceListingLines;

    protected function prepareForValidation(): void
    {
        if ($this->input('schema_json') === '') {
            $this->merge(['schema_json' => null]);
        }

        $this->normalizeServiceKeywordArrays();
        $this->normalizeServiceListingLines();

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
            'short_summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'procedures_lines' => ['nullable', 'string', 'max:10000'],
            'specialized_care_lines' => ['nullable', 'string', 'max:10000'],
            'shifts_lines' => ['nullable', 'string', 'max:10000'],
            'procedures' => ['nullable', 'array'],
            'procedures.*' => ['string', 'max:500'],
            'specialized_care' => ['nullable', 'array'],
            'specialized_care.*' => ['string', 'max:500'],
            'shifts' => ['nullable', 'array'],
            'shifts.*' => ['string', 'max:500'],
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

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'service_code' => __('service code'),
            'seo.meta_title' => __('meta title'),
            'schema_json' => __('schema JSON'),
        ];
    }
}
