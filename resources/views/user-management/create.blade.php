<x-app-layout
    :page-title="__('Create user')"
    :welcome-line="__('Provision identity, role label, and module access.')"
>
    <div class="mx-auto max-w-3xl">
        <form method="post" action="{{ route('user-management.store') }}" class="space-y-8" enctype="multipart/form-data">
            @csrf

            <div class="mom-card space-y-6 p-6">
                <h2 class="mom-section-title">{{ __('Profile') }}</h2>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input-label for="name" :value="__('Full name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus variant="mom" />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required variant="mom" />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>
                    <div>
                        <x-input-label for="phone" :value="__('Phone')" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" variant="mom" />
                        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="role_label" :value="__('Role label')" />
                        <x-text-input
                            id="role_label"
                            name="role_label"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('role_label')"
                            placeholder="{{ __('e.g. Operations Staff') }}"
                            variant="mom"
                        />
                        <p class="mom-subtext mt-1">{{ __('Operational designation only � access is controlled by modules below.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('role_label')" />
                    </div>
                    <div>
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required variant="mom" />
                        <x-input-error class="mt-2" :messages="$errors->get('password')" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm password')" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required variant="mom" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="profile_image" :value="__('Profile image')" />
                        <input
                            id="profile_image"
                            name="profile_image"
                            type="file"
                            accept="image/*"
                            class="mt-1 block w-full text-sm text-[var(--text-secondary)] file:mr-4 file:rounded-mom-md file:border-0 file:bg-[rgba(197,160,89,0.12)] file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-wide file:text-mom-gold"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('profile_image')" />
                    </div>
                    <div class="sm:col-span-2">
                        <input type="hidden" name="is_active" value="0" />
                        <label class="flex cursor-pointer items-center gap-3 rounded-mom-md border border-[rgba(255,255,255,0.045)] bg-[var(--bg-card-nested)] p-4">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                class="h-4 w-4 rounded border-[rgba(255,255,255,0.12)] bg-[rgba(28,22,18,0.75)] text-mom-gold"
                                @checked(old('is_active', true))
                            />
                            <span class="text-sm font-medium text-[var(--text-primary)]">{{ __('Account active') }}</span>
                        </label>
                        <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
                    </div>
                </div>
            </div>

            <div class="mom-card space-y-5 p-6">
                <h2 class="mom-section-title">{{ __('Module access') }}</h2>
                <p class="mom-body-text text-[var(--text-secondary)]">
                    {{ __('Checked modules appear in the sidebar and unlock matching dashboard surfaces.') }}
                </p>
                <div class="space-y-3">
                    @include('user-management.partials.module-access-fields', ['user' => null])
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <x-primary-button variant="mom">{{ __('Create user') }}</x-primary-button>
                <a
                    href="{{ route('user-management.index') }}"
                    class="mom-cta-ghost"
                >{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
