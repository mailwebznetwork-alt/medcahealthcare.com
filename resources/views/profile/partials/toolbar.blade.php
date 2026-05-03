@php
    $profileActive = request()->routeIs('profile.edit');
@endphp

<div class="mom-module-toolbar-host">
    <div class="mom-module-toolbar-host__inner py-4">
        <nav class="flex flex-wrap gap-3" aria-label="{{ __('Account') }}">
            <a
                href="{{ route('dashboard') }}"
                class="mom-toolbar-pill mom-toolbar-pill-muted"
            >{{ __('Dashboard') }}</a>
            <a
                href="{{ route('profile.edit') }}"
                @class([
                    'mom-toolbar-pill',
                    'mom-toolbar-pill-active' => $profileActive,
                    'mom-toolbar-pill-muted' => ! $profileActive,
                ])
            >{{ __('Profile') }}</a>
        </nav>
    </div>
</div>
