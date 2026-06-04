<?php

namespace App\Http\Requests\Operations\ServiceCategories;

use App\Http\Requests\Concerns\InteractsWithArchitectSavePolicy;
use App\Models\ServiceCategory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceCategoryRequest extends FormRequest
{
    use InteractsWithArchitectSavePolicy;
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => ServiceCategory::normalizeCode((string) $this->input('code', '')),
            ]);
        }
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
            $this->architectAssertIncompleteAcknowledged($validator, ['name', 'code']);
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

        return $this->architectPrepareRules([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z][a-z0-9-]*$/',
            ],
            'description' => ['nullable', 'string', 'max:65535'],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:service_categories,id',
                Rule::notIn([$category->id]),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ], ['name', 'code']);
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
