<?php

namespace App\Http\Requests\UserManagement;

use App\Models\User;
use App\ModuleAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'max:32'],
            'role_label' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'profile_image' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        foreach (ModuleAccess::keys() as $key) {
            $rules['module_access.'.$key] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    /**
     * Unchecked boxes are absent — normalize every module key to a boolean.
     *
     * @return array<string, bool>
     */
    public function normalizedModuleAccess(): array
    {
        $normalized = [];
        foreach (ModuleAccess::keys() as $key) {
            $normalized[$key] = $this->boolean('module_access.'.$key);
        }

        return $normalized;
    }
}
