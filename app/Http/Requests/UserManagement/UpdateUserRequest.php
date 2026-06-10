<?php

namespace App\Http\Requests\UserManagement;

use App\Models\User;
use App\Rules\ProductionStaffEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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
            'password' => ['nullable', Password::defaults(), 'confirmed'],
            'admin_password' => ['required_with:password', 'string'],
            'module_access' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            /** @var User|null $actor */
            $actor = $this->user();
            if ($actor === null) {
                return;
            }

            $newRole = (string) $this->input('role', '');
            $actorRole = strtolower(trim((string) ($actor->role ?? '')));

            if ($actorRole === 'manager' && in_array($newRole, ['admin', 'super_admin'], true)) {
                $validator->errors()->add('role', __('Managers cannot assign administrator roles.'));
            }

            if ($newRole === 'super_admin' && $actorRole !== 'super_admin' && ! $actor->isRootSuperAdmin()) {
                $validator->errors()->add('role', __('Only a super administrator can assign the super admin role.'));
            }
        });
    }
}
