@php
    $overviewActive = request()->routeIs('operations.pin-codes.overview');
    $directoryActive = request()->routeIs(
        'operations.pin-codes.directory',
        'operations.pin-codes.create',
        'operations.pin-codes.edit',
    );
    $bulkImportActive = request()->routeIs(
        'operations.pin-codes.bulk-import',
        'operations.pin-codes.bulk-import.preview',
        'operations.pin-codes.bulk-import.confirm',
        'operations.pin-codes.bulk-import.cancel',
    );
@endphp

<nav class="flex flex-wrap gap-3" aria-label="{{ __('Pin codes') }}">
    @can('create', \App\Models\PinCode::class)
        <a href="{{ route('operations.pin-codes.create') }}" class="mom-toolbar-pill mom-toolbar-pill-gold">{{ __('Add pin code') }}</a>
    @endcan
    <a
        href="{{ route('operations.pin-codes.overview') }}"
        @class([
            'mom-toolbar-pill',
            'mom-toolbar-pill-active' => $overviewActive,
            'mom-toolbar-pill-muted' => ! $overviewActive,
        ])
    >{{ __('Overview') }}</a>
    <a
        href="{{ route('operations.pin-codes.directory') }}"
        @class([
            'mom-toolbar-pill',
            'mom-toolbar-pill-active' => $directoryActive,
            'mom-toolbar-pill-muted' => ! $directoryActive,
        ])
    >{{ __('Directory') }}</a>
    @can('import', \App\Models\PinCode::class)
        <a
            href="{{ route('operations.pin-codes.bulk-import') }}"
            @class([
                'mom-toolbar-pill',
                'mom-toolbar-pill-active' => $bulkImportActive,
                'mom-toolbar-pill-muted' => ! $bulkImportActive,
            ])
        >{{ __('Bulk import') }}</a>
    @endcan
</nav>
