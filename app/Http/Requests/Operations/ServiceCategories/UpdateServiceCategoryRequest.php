<?php

namespace App\Http\Requests\Operations\ServiceCategories;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Http\Requests\Concerns\InteractsWithArchitectSavePolicy;
use App\Http\Requests\Operations\Concerns\ValidatesCatalogExtendedFields;
use App\Http\Requests\Operations\Services\Concerns\NormalizesServiceKeywordArrays;
use App\Http\Requests\Operations\Services\Concerns\NormalizesServiceListingLines;
use App\Models\ServiceCategory;
use App\Rules\RejectFakerContent;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceCategoryRequest extends FormRequest
{
     use InteractsWithArchitectSavePolicy;
    use NormalizesServiceKeywordArrays;
    use NormalizesServiceListingLines;
    use ValidatesCatalogExtendedFields;

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => ServiceCategory::normalizeCode((string) $this->input('code', '')),
            ]);
        }

        $this->normalizeServiceListingLines();
        $this->normalizeServiceKeywordArrays();
    }

    public function authorize(): bool
    {
        $category = $this->route('service_category');

        return $category instanceof ServiceCategory
            && ($this->user()?->can('update', $category) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function withValidator(Validator $validator): void
    {
        /** @var ServiceCategory $category */
        $category = $this->route('service_category');

        $validator->after(function (Validator $validator) use ($category): void {
            $this->architectAssertIncompleteAcknowledged($validator, ['name', 'code', 'publish_status', 'visibility']);
            $this->architectAssertUnique(
                $validator,
                ServiceCategory::class,
                'code',
                $this->input('code'),
                $category->id,
                'name'
            );
        });
    }

    public function rules(): array
    {
        /** @var ServiceCategory $category */
        $category = $this->route('service_category');

        return $this->architectPrepareRules(array_merge([
            'name' => ['required', 'string', 'max:255', new RejectFakerContent],
            'code' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z][a-z0-9-]*$/',
                new RejectFakerContent,
            ],
            'slug' => ['nullable', 'string', 'max:120', new RejectFakerContent],
            'description' => ['nullable', 'string', new RejectFakerContent],
            'short_summary' => ['nullable', 'string', 'max:65535', new RejectFakerContent],
            'price_range' => ['nullable', 'string', 'max:120'],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:service_categories,id',
                Rule::notIn([$category->id]),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'publish_status' => ['required', Rule::enum(PublishStatus::class)],
            'visibility' => ['required', Rule::enum(ServiceVisibility::class)],
            'show_on_homepage' => ['boolean'],
            'show_on_about' => ['boolean'],
            'show_on_contact' => ['boolean'],
            'page_id' => ['nullable', 'integer', 'exists:pages,id'],
            'procedures_lines' => ['nullable', 'string'],
            'procedures' => ['nullable', 'array'],
            'procedures.*' => ['string', 'max:500'],
        ], $this->extendedCatalogFieldRules()), ['name', 'code', 'publish_status', 'visibility']);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => __('category code'),
            'parent_id' => __('parent category'),
        ];
    }
}
