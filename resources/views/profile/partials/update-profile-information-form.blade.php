<section>
    <header>
        <h2 class="mom-section-title">
            {{ __('Profile Information') }}
        </h2>

        <p class="mom-body-text mt-2">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" variant="mom" />
            <x-text-input id="name" name="name" type="text" class="mt-2 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" variant="mom" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" variant="mom" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" variant="mom" />
            <x-text-input id="email" name="email" type="email" class="mt-2 block w-full" :value="old('email', $user->email)" required autocomplete="username" variant="mom" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" variant="mom" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="mom-body-text mt-3">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" type="submit" class="text-[var(--accent-gold)] underline decoration-[rgba(212,169,95,0.35)] underline-offset-2 transition-colors duration-320 ease-premium hover:text-[var(--text-primary)]">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-[var(--success)]">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button variant="mom">{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
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
