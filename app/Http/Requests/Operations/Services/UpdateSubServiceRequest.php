<?php

namespace App\Http\Requests\Operations\Services;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Http\Requests\Concerns\InteractsWithArchitectSavePolicy;
use App\Http\Requests\Operations\Concerns\ValidatesCatalogExtendedFields;
use App\Http\Requests\Operations\Services\Concerns\NormalizesServiceKeywordArrays;
use App\Http\Requests\Operations\Services\Concerns\NormalizesServiceListingLines;
use App\Models\Service;
use App\Models\SubService;
use App\Rules\RejectFakerContent;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubServiceRequest extends FormRequest
{
    use InteractsWithArchitectSavePolicy;
    use NormalizesServiceKeywordArrays;
    use NormalizesServiceListingLines;
    use ValidatesCatalogExtendedFields;

    protected function prepareForValidation(): void
    {
        $code = strtolower(trim((string) $this->input('sub_service_code', '')));
        $code = str_replace([' ', '_'], '-', $code);
        if ($code !== '') {
            $this->merge(['sub_service_code' => $code]);
        }

        $this->normalizeServiceListingLines();
        $this->normalizeServiceKeywordArrays();
    }

    public function authorize(): bool
    {
        $service = $this->route('service');
        $subService = $this->route('sub_service');

        return $service instanceof Service
            && $subService instanceof SubService
            && (int) $subService->service_id === (int) $service->id
            && $this->user()?->can('update', $subService) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function withValidator(Validator $validator): void
    {
        /** @var Service $service */
        $service = $this->route('service');
        /** @var SubService $subService */
        $subService = $this->route('sub_service');

        $validator->after(function (Validator $validator) use ($service, $subService): void {
            $this->architectAssertIncompleteAcknowledged($validator, ['title', 'sub_service_code', 'publish_status', 'visibility']);
            $this->architectAssertUnique(
                $validator,
                SubService::class,
                'sub_service_code',
                $this->input('sub_service_code'),
                $subService->id,
                'title'
            );
        });
    }

    public function rules(): array
    {
        /** @var Service $service */
        $service = $this->route('service');
        /** @var SubService $subService */
        $subService = $this->route('sub_service');

        return $this->architectPrepareRules(array_merge([
            'sub_service_code' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('sub_services', 'sub_service_code')
                    ->where('service_id', $service->id)
                    ->ignore($subService->id),
                new RejectFakerContent,
            ],
            'title' => ['required', 'string', 'max:255', new RejectFakerContent],
            'short_summary' => ['nullable', 'string', 'max:65535', new RejectFakerContent],
            'description' => ['nullable', 'string', new RejectFakerContent],
            'price_range' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_top_rated' => ['nullable', 'boolean'],
            'show_on_homepage' => ['nullable', 'boolean'],
            'show_on_about' => ['nullable', 'boolean'],
            'show_on_contact' => ['nullable', 'boolean'],
            'publish_status' => ['required', Rule::enum(PublishStatus::class)],
            'visibility' => ['required', Rule::enum(ServiceVisibility::class)],
            'page_id' => ['nullable', 'integer', 'exists:pages,id'],
            'pincodes' => ['nullable', 'array'],
            'pincodes.*' => ['integer', 'exists:pin_codes,id'],
            'procedures_lines' => ['nullable', 'string'],
            'procedures' => ['nullable', 'array'],
            'procedures.*' => ['string', 'max:500'],
        ], $this->extendedCatalogFieldRules()), ['title', 'sub_service_code', 'publish_status', 'visibility']);
    }
}
