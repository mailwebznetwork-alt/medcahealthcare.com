<?php

namespace App\Http\Requests\UserManagement;

use App\Models\User;
use App\ModuleAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        if (! $target instanceof User) {
            return false;
        }

        return $this->user()?->can('update', $target) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        $emailRules = [
            'required',
            'string',
            'lowercase',
            'email',
            'max:255',
            Rule::unique(User::class)->ignore($target->id),
        ];

        if ($target->isRootSuperAdmin()) {
            $emailRules[] = Rule::in([strtolower($target->email)]);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
            'phone' => ['nullable', 'string', 'max:32'],
            'role_label' => ['nullable', 'string', 'max:120'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'profile_image' => ['nullable', 'image', 'max:2048'],
            'remove_profile_image' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        foreach (ModuleAccess::keys() as $key) {
            $rules['module_access.'.$key] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    /**
     * @return array<string, bool>
     */
    public function normalizedModuleAccess(User $target): array
    {
        if ($target->isRootSuperAdmin()) {
            return ModuleAccess::defaultGrants();
        }

        $normalized = [];
        foreach (ModuleAccess::keys() as $key) {
            $normalized[$key] = $this->boolean('module_access.'.$key);
        }

        return $normalized;
    }

    protected function prepareForValidation(): void
    {
        /** @var User $target */
        $target = $this->route('user');

        if ($target->isRootSuperAdmin()) {
            $this->merge([
                'email' => $target->email,
                'role_label' => $target->role_label,
            ]);
        }
    }
}
