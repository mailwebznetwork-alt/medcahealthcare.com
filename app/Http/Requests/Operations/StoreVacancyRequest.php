<?php

namespace App\Http\Requests\Operations;

use App\Enums\EmploymentType;
use App\Enums\VacancyVisibility;
use App\Enums\VacancyWorkflowStatus;
use App\Models\Vacancy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVacancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Vacancy::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'area' => ['nullable', 'string', 'max:120'],
            'pin_code' => ['nullable', 'string', 'max:16'],
            'country_code' => ['nullable', 'string', 'max:4'],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'salary_currency' => ['nullable', 'string', 'max:8'],
            'closing_date' => ['nullable', 'date'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'whatsapp_apply_url' => ['nullable', 'string', 'max:2048', 'url'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:5000'],
            'focus_keywords' => ['nullable', 'string', 'max:500'],
            'ai_context' => ['nullable', 'string'],
            'visibility' => ['required', Rule::enum(VacancyVisibility::class)],
            'workflow_status' => ['required', Rule::enum(VacancyWorkflowStatus::class)],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:999999'],
            'detail_page_id' => ['nullable', 'integer', 'exists:pages,id'],
        ];
    }
}
