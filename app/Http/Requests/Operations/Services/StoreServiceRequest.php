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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    use InteractsWithArchitectSavePolicy;
    use NormalizesServiceKeywordArrays;
    use NormalizesServiceListingLines;
    use ValidatesServiceExtendedFields;

    protected function prepareForValidation(): void
    {
        $code = strtolower(trim((string) $this->input('service_code', '')));
        $code = str_replace([' ', '_'], '-', $code);
        if ($code !== '') {
            $this->merge(['service_code' => $code]);
        }

        $this->normalizeServiceListingLines();
        $this->normalizeServiceKeywordArrays();

        if ($this->architectSaveBypassEligible() && $this->architectIncompleteSaveApproved()) {
            $merge = [];
            if (trim((string) $this->input('title', '')) === '') {
                $merge['title'] = __('Untitled service');
            }
            if (trim((string) $this->input('service_code', '')) === '') {
                $merge['service_code'] = 'service-'.Str::lower(Str::random(8));
            }
            if (trim((string) $this->input('publish_status', '')) === '') {
                $merge['publish_status'] = PublishStatus::Draft->value;
            }
            if (trim((string) $this->input('visibility', '')) === '') {
                $merge['visibility'] = ServiceVisibility::Private->value;
            }
            if ($merge !== []) {
                $this->merge($merge);
            }
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', Service::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->architectAssertIncompleteAcknowledged($validator, ['title', 'service_code', 'publish_status', 'visibility']);
            $this->architectAssertUnique(
                $validator,
                Service::class,
                'service_code',
                $this->input('service_code'),
                null,
                'title'
            );
        });
    }

    public function rules(): array
    {
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
        ], ['title', 'service_code', 'publish_status', 'visibility']);
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
