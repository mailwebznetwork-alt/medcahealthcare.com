<x-app-layout
    :page-title="__('Profile')"
    :welcome-line="__('Manage your identity, credentials, and account lifecycle.')"
>
    <div class="w-full max-w-full">
        <div class="mom-card p-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        <hr class="mom-section-separator" aria-hidden="true" />

        <div class="mom-card p-6">
            @include('profile.partials.update-password-form')
        </div>

        <hr class="mom-section-separator" aria-hidden="true" />

        @unless ($user->isRootSuperAdmin())
            <div class="mom-card border-[rgba(226,92,92,0.15)] p-6 shadow-[inset_0_1px_0_rgba(255,255,255,0.04)]">
                @include('profile.partials.delete-user-form')
            </div>
        @endunless
    </div>
</x-app-layout>
