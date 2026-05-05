<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = mb_strtolower((string) $request->input('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->activityLogService->log(
                'login_failure',
                'auth',
                sprintf('Rate limit exceeded for IP %s.', (string) $request->ip())
            );

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => RateLimiter::availableIn($throttleKey),
                    'minutes' => (int) ceil(RateLimiter::availableIn($throttleKey) / 60),
                ]),
            ]);
        }

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            $this->activityLogService->log(
                'login_failure',
                'auth',
                sprintf('Failed login for email %s from IP %s.', (string) $request->input('email'), (string) $request->ip())
            );

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = Auth::user();

        if ($user !== null && ! $user->is_active) {
            Auth::guard('web')->logout();
            RateLimiter::hit($throttleKey, 60);

            $this->activityLogService->log(
                'login_failure',
                'auth',
                sprintf('Inactive account login blocked for %s.', (string) $request->input('email'))
            );

            throw ValidationException::withMessages([
                'email' => __('This account has been deactivated. Contact an administrator.'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();
        $request->session()->put('last_activity', time());

        $this->activityLogService->log(
            'login_success',
            'auth',
            sprintf('Successful login for %s.', (string) $request->input('email'))
        );

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->activityLogService->log('logout', 'auth', 'User logged out.');

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect('/');
    }
}
