<?php

namespace App\Http\Requests\UserManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id ?? null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'profile_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,msword', 'max:2048'],
            'role' => ['required', 'string', 'in:super_admin,admin,manager,editor,viewer'],
        ];
    }
}
