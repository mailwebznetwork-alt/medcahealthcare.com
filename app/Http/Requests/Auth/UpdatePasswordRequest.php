<?php

namespace App\Http\Requests\Auth;

use App\Services\Security\PasswordSecurityService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        app(PasswordSecurityService::class)->logFailure(
            $this,
            'profile_password_update',
            'validation_failed',
            $this->user()?->id,
        );

        throw (new ValidationException($validator))->errorBag('updatePassword');
    }
}
