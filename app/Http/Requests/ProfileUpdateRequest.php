<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\RootAccount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $user = $this->user();
        if ($user instanceof User && RootAccount::isRootUser($user)) {
            $this->merge([
                'email' => $user->email,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $user = $this->user();
            if ($user instanceof User && RootAccount::isRootUser($user)) {
                if (strtolower((string) $this->input('email')) !== strtolower($user->email)) {
                    $validator->errors()->add('email', __('The root administrator email cannot be changed.'));
                }
            }
        });
    }
}
