<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Integrations\OutboundWebhookDispatcher;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'module_access' => array_merge(
                array_fill_keys(ModuleAccess::keys(), false),
                [ModuleAccess::DASHBOARD => true],
            ),
            'is_active' => true,
        ]);

        event(new Registered($user));

        app(OutboundWebhookDispatcher::class)->dispatch('user.registered', [
            'user_id' => $user->id,
            'registration' => 'self_service',
        ]);

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
