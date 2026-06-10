<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Services\Security\PasswordSecurityService;
use Illuminate\Http\RedirectResponse;

class PasswordController extends Controller
{
    public function __construct(private readonly PasswordSecurityService $passwordSecurity) {}

    /**
     * Update the authenticated user's password.
     */
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $this->passwordSecurity->changePassword(
            $user,
            $validated['password'],
            'profile_password_update',
            $user->id,
        );

        return back()->with('status', 'password-updated');
    }
}
