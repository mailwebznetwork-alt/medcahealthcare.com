<?php

namespace App\Http\Requests\UserManagement;

use App\Models\User;
use App\Rules\ProductionStaffEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('role')) {
            $this->merge(['role' => 'viewer']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', new ProductionStaffEmail],
            'phone' => ['nullable', 'string', 'max:64'],
            'role_label' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'profile_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,msword', 'max:2048'],
            'role' => ['required', 'string', 'in:super_admin,admin,manager,editor,viewer,content_writer,medical_reviewer'],
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
