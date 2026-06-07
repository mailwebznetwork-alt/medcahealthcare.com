@php
    $indexActive = request()->routeIs('operations.services.index');
    $bulkImportActive = request()->routeIs(
        'operations.services.bulk-import',
        'operations.services.bulk-import.preview',
        'operations.services.bulk-import.confirm',
        'operations.services.bulk-import.cancel',
    );
    $editing = request()->routeIs('operations.services.create', 'operations.services.edit', 'operations.services.preview');
@endphp

<nav class="flex flex-wrap gap-3" aria-label="{{ __('Services') }}">
    <a href="{{ route('operations.services.create') }}" class="mom-cta-primary">{{ __('Create service') }}</a>
    <a
        href="{{ route('operations.services.index') }}"
        @class([
            'mom-cta-ghost',
            'mom-cta-ghost--active' => $indexActive && ! $editing && ! $bulkImportActive,
        ])
    >{{ __('All services') }}</a>
    @can('viewAny', \App\Models\Service::class)
        <a
            href="{{ route('operations.services.bulk-import') }}"
            @class([
                'mom-cta-ghost',
                'mom-cta-ghost--active' => $bulkImportActive,
            ])
        >{{ __('Bulk import') }}</a>
    @endcan
</nav>
