@php
    $indexActive = request()->routeIs('user-management.index');
    $createActive = request()->routeIs('user-management.create');
    $editActive = request()->routeIs('user-management.edit');
@endphp

<div class="mom-module-toolbar-host">
    <div class="mom-module-toolbar-host__inner py-4">
        <nav class="flex flex-wrap gap-3" aria-label="{{ __('User management') }}">
            <a
                href="{{ route('user-management.create') }}"
                @class([
                    'mom-toolbar-pill',
                    'mom-toolbar-pill-active' => $createActive,
                    'mom-toolbar-pill-gold' => ! $createActive,
                ])
            >{{ __('New user') }}</a>
            <a
                href="{{ route('user-management.index') }}"
                @class([
                    'mom-toolbar-pill',
                    'mom-toolbar-pill-active' => $indexActive,
                    'mom-toolbar-pill-muted' => ! $indexActive,
                ])
            >{{ __('All users') }}</a>
            @if ($editActive)
                <span class="mom-toolbar-pill mom-toolbar-pill-active cursor-default" aria-current="page">{{ __('Editing user') }}</span>
            @endif
        </nav>
    </div>
</div>
