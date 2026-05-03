<section class="space-y-6">
    <header>
        <h2 class="mom-section-title">
            {{ __('Delete Account') }}
        </h2>

        <p class="mom-body-text mt-2">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <x-danger-button
        variant="mom"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete Account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" variant="mom" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="mom-section-title">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mom-body-text mt-2">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" variant="mom" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-2 block w-full sm:w-3/4"
                    placeholder="{{ __('Password') }}"
                    variant="mom"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" variant="mom" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button variant="mom" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button variant="mom" class="ms-0">
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
