<?php

namespace App\Http\Requests\Operations\ServiceCategories;

use App\Http\Requests\Concerns\InteractsWithArchitectSavePolicy;
use App\Models\ServiceCategory;
use App\Rules\RejectFakerContent;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreServiceCategoryRequest extends FormRequest
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
        return $this->user()?->can('create', ServiceCategory::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->architectAssertIncompleteAcknowledged($validator, ['name', 'code']);
            $this->architectAssertUnique(
                $validator,
                ServiceCategory::class,
                'code',
                $this->input('code'),
                null,
                'name'
            );
        });
    }

    public function rules(): array
    {
        return $this->architectPrepareRules([
            'name' => ['required', 'string', 'max:255', new RejectFakerContent],
            'code' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z][a-z0-9-]*$/',
                new RejectFakerContent,
            ],
            'description' => ['nullable', 'string', 'max:65535', new RejectFakerContent],
            'parent_id' => ['nullable', 'integer', 'exists:service_categories,id'],
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
