<x-user-management.shell :page-title="__('Edit user')" :welcome-line="$user->email">
    <div class="mx-auto max-w-3xl">
        <form method="post" action="{{ route('user-management.update', $user) }}" class="space-y-8" enctype="multipart/form-data">
            @csrf
            @method('put')

            @if ($user->isRootSuperAdmin())
                <div class="rounded-mom-md border border-[rgba(212,169,95,0.22)] bg-[rgba(212,169,95,0.06)] px-4 py-3 text-sm text-[var(--text-secondary)]">
                    {{ __('This is the protected root administrator. Module access stays fully enabled; destructive actions are disabled for every account.') }}
                </div>
            @endif

            <div class="mom-card space-y-6 p-6">
                <h2 class="mom-section-title">{{ __('Profile') }}</h2>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input-label for="name" :value="__('Full name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus variant="mom" />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input
                            id="email"
                            name="email"
                            type="email"
                            class="mt-1 block w-full"
                            :value="old('email', $user->email)"
                            :disabled="$user->isRootSuperAdmin()"
                            required
                            variant="mom"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>
                    <div>
                        <x-input-label for="phone" :value="__('Phone')" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" variant="mom" />
                        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="role_label" :value="__('Role label')" />
                        <x-text-input
                            id="role_label"
                            name="role_label"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('role_label', $user->role_label)"
                            :disabled="$user->isRootSuperAdmin()"
                            variant="mom"
                        />
                        @if ($user->isRootSuperAdmin())
                            <p class="mom-subtext mt-1">{{ __('Role label is locked for the root administrator.') }}</p>
                        @endif
                        <x-input-error class="mt-2" :messages="$errors->get('role_label')" />
                    </div>
                    <div>
                        <x-input-label for="password" :value="__('New password (optional)')" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" variant="mom" />
                        <x-input-error class="mt-2" :messages="$errors->get('password')" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm new password')" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" variant="mom" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="profile_image" :value="__('Profile image')" />
                        @if ($user->profile_image_path)
                            <div class="mt-2 flex items-center gap-4">
                                <img src="{{ $user->profileImageUrl() }}" alt="" class="h-14 w-14 rounded-full border border-[rgba(255,255,255,0.06)] object-cover" />
                                <label class="flex items-center gap-2 text-sm text-[var(--text-secondary)]">
                                    <input type="checkbox" name="remove_profile_image" value="1" class="h-4 w-4 rounded border-[rgba(255,255,255,0.12)] text-mom-gold" />
                                    {{ __('Remove current image') }}
                                </label>
                            </div>
                        @endif
                        <input
                            id="profile_image"
                            name="profile_image"
                            type="file"
                            accept="image/*"
                            class="mt-2 block w-full text-sm text-[var(--text-secondary)] file:mr-4 file:rounded-mom-md file:border-0 file:bg-[rgba(212,169,95,0.12)] file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-wide file:text-mom-gold"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('profile_image')" />
                    </div>
                    @unless ($user->isRootSuperAdmin())
                        <div class="sm:col-span-2">
                            <input type="hidden" name="is_active" value="0" />
                            <label class="flex cursor-pointer items-center gap-3 rounded-mom-md border border-[rgba(255,255,255,0.045)] bg-[var(--bg-card-nested)] p-4">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    class="h-4 w-4 rounded border-[rgba(255,255,255,0.12)] bg-[rgba(28,22,18,0.75)] text-mom-gold"
                                    @checked(old('is_active', $user->is_active))
                                />
                                <span class="text-sm font-medium text-[var(--text-primary)]">{{ __('Account active') }}</span>
                            </label>
                            <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
                        </div>
                    @endunless
                </div>
            </div>

            <div class="mom-card space-y-5 p-6">
                <h2 class="mom-section-title">{{ __('Module access') }}</h2>
                <p class="mom-body-text text-[var(--text-secondary)]">
                    {{ __('Checked modules appear in the sidebar and unlock matching dashboard surfaces.') }}
                </p>
                <div class="space-y-3">
                    @include('user-management.partials.module-access-fields', ['user' => $user])
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <x-primary-button variant="mom">{{ __('Save changes') }}</x-primary-button>
                <a
                    href="{{ route('user-management.index') }}"
                    class="inline-flex items-center justify-center rounded-mom-md border border-[rgba(255,255,255,0.045)] bg-[rgba(255,255,255,0.03)] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-[var(--text-secondary)] shadow-mom-inner transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.16)] hover:text-[var(--text-primary)]"
                >{{ __('Back to directory') }}</a>
            </div>
        </form>
    </div>
</x-user-management.shell>
