<?php

namespace App\Http\Requests\UserManagement;

use App\Rules\ProductionStaffEmail;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', new ProductionStaffEmail],
            'password' => [
                'required',
                'confirmed',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
            ],
            'profile_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,msword', 'max:2048'],
            'role' => ['required', 'string', 'in:super_admin,admin,manager,editor,viewer'],
        ];
    }
}
