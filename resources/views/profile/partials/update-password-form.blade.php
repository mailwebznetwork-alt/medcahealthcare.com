<section>
    <header>
        <h2 class="mom-section-title">
            {{ __('Update Password') }}
        </h2>

        <p class="mom-body-text mt-2">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" variant="mom" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-2 block w-full" autocomplete="current-password" variant="mom" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" variant="mom" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" variant="mom" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-2 block w-full" autocomplete="new-password" variant="mom" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" variant="mom" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" variant="mom" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-2 block w-full" autocomplete="new-password" variant="mom" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" variant="mom" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button variant="mom">{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-[var(--text-secondary)]"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
