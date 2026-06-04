@php
    $indexActive = request()->routeIs('operations.service-categories.index');
    $editing = request()->routeIs('operations.service-categories.create', 'operations.service-categories.edit');
@endphp

<nav class="flex flex-wrap gap-3" aria-label="{{ __('Service categories') }}">
    @can('create', \App\Models\ServiceCategory::class)
        <a href="{{ route('operations.service-categories.create') }}" class="mom-cta-primary">{{ __('Add category') }}</a>
    @endcan
    <a
        href="{{ route('operations.service-categories.index') }}"
        @class([
            'mom-cta-ghost',
            'mom-cta-ghost--active' => $indexActive && ! $editing,
        ])
    >{{ __('All categories') }}</a>
    <a href="{{ route('public.service-categories.index') }}" class="mom-cta-ghost" target="_blank" rel="noopener">{{ __('View public index') }}</a>
</nav>
