<?php

namespace App\Http\Requests\Operations\Services;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Http\Requests\Operations\Services\Concerns\NormalizesServiceKeywordArrays;
use App\Http\Requests\Operations\Services\Concerns\NormalizesServiceListingLines;
use App\Http\Requests\Operations\Services\Concerns\ValidatesServiceExtendedFields;
use App\Http\Requests\Concerns\InteractsWithArchitectSavePolicy;
use App\Models\Service;
use App\Rules\RejectFakerContent;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    use InteractsWithArchitectSavePolicy;
    use NormalizesServiceKeywordArrays;
    use NormalizesServiceListingLines;
    use ValidatesServiceExtendedFields;

    protected function prepareForValidation(): void
    {
        $this->normalizeServiceListingLines();
        $this->normalizeServiceKeywordArrays();
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
    public function withValidator(Validator $validator): void
    {
        /** @var Service $service */
        $service = $this->route('service');

        $validator->after(function (Validator $validator) use ($service): void {
            $this->architectAssertIncompleteAcknowledged($validator, ['title', 'publish_status', 'visibility']);
            $this->architectAssertUnique(
                $validator,
                Service::class,
                'service_code',
                $this->input('service_code'),
                $service->id,
                'title'
            );
        });
    }

    public function rules(): array
    {
        /** @var Service $service */
        $service = $this->route('service');

        return $this->architectPrepareRules([
            'title' => ['required', 'string', 'max:255', new RejectFakerContent],
            'service_code' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z][a-zA-Z0-9_-]*$/', new RejectFakerContent],
            'price_range' => ['nullable', 'string', 'max:120'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'publish_status' => ['required', Rule::enum(PublishStatus::class)],
            'visibility' => ['required', Rule::enum(ServiceVisibility::class)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'detail_page_id' => ['nullable', 'integer', 'exists:pages,id'],
            'pincodes' => ['nullable', 'array'],
            'pincodes.*' => ['integer', 'exists:pin_codes,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:service_categories,id'],
            'short_summary' => ['nullable', 'string', 'max:65535', new RejectFakerContent],
            'description' => ['nullable', 'string', new RejectFakerContent],
            'procedures_lines' => ['nullable', 'string'],
            'procedures' => ['nullable', 'array'],
            'procedures.*' => ['string', 'max:500'],
            'featured_image' => ['nullable', 'image', 'max:5120'],
            'gallery_files' => ['nullable', 'array'],
            'gallery_files.*' => ['image', 'max:5120'],
            'remove_gallery' => ['nullable', 'array'],
            'remove_gallery.*' => ['string', 'max:500'],
            ...$this->extendedServiceFieldRules(),
        ], ['title', 'publish_status', 'visibility']);
    }
}
