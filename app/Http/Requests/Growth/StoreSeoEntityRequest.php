<?php

namespace App\Http\Requests\Growth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSeoEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:2048'],
            'og_image_url' => ['nullable', 'string', 'max:2048'],
            'same_as' => ['nullable', 'array'],
            'same_as.*' => ['nullable', 'url', 'max:2048'],
            'same_as_json' => ['nullable', 'string', 'json'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'phone_e164' => ['nullable', 'string', 'max:32'],
            'country_code' => ['nullable', 'string', 'max:8'],
            'street_address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:64'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'custom_json_ld_raw' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $raw = $this->input('custom_json_ld_raw');
            if (! is_string($raw) || trim($raw) === '') {
                return;
            }

            json_decode($raw);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $validator->errors()->add('custom_json_ld_raw', __('Must be valid JSON.'));

                return;
            }

            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                $validator->errors()->add('custom_json_ld_raw', __('JSON-LD must be a JSON array or object.'));
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('same_as_json')) {
            return;
        }

        $raw = $this->input('same_as_json');
        if (! is_string($raw) || trim($raw) === '') {
            return;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return;
        }

        $urls = [];
        foreach ($decoded as $item) {
            if (is_string($item) && filter_var($item, FILTER_VALIDATE_URL)) {
                $urls[] = $item;
            }
        }

        $this->merge(['same_as' => $urls]);
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        /** @var array<string, mixed> $data */
        $data = parent::validated($key, $default);

        if (isset($data['custom_json_ld_raw']) && is_string($data['custom_json_ld_raw']) && trim($data['custom_json_ld_raw']) !== '') {
            $data['custom_json_ld'] = json_decode($data['custom_json_ld_raw'], true);
        } else {
            $data['custom_json_ld'] = null;
        }

        unset($data['custom_json_ld_raw'], $data['same_as_json']);

        return $data;
    }
}
