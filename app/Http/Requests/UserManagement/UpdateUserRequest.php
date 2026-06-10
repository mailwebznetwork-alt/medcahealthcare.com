<?php

namespace App\Http\Requests\UserManagement;

use App\Models\User;
use App\Rules\ProductionStaffEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        /** @var User|null $routeUser */
        $routeUser = $this->route('user');

        if ($routeUser instanceof User && ! $this->filled('role')) {
            $role = $routeUser->role instanceof \BackedEnum ? $routeUser->role->value : (string) ($routeUser->role ?? 'viewer');
            $this->merge(['role' => $role]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id ?? null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId), new ProductionStaffEmail],
            'phone' => ['nullable', 'string', 'max:64'],
            'role_label' => ['nullable', 'string', 'max:255'],
            'profile_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,msword', 'max:2048'],
            'remove_profile_image' => ['sometimes', 'boolean'],
            'role' => ['required', 'string', 'in:super_admin,admin,manager,editor,viewer'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'module_access' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
