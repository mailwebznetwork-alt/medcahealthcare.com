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
        <a
            href="{{ route('operations.pin-codes.create') }}"
            class="inline-flex items-center justify-center rounded-mom-md border border-[rgba(212,169,95,0.28)] bg-[linear-gradient(180deg,rgba(212,169,95,0.22),rgba(212,169,95,0.12))] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-[#0a0a0a] shadow-[0_0_24px_rgba(212,169,95,0.15)] transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.4)] hover:shadow-[0_0_32px_rgba(212,169,95,0.22)]"
        >{{ __('Add pin code') }}</a>
    @endcan
    <a
        href="{{ route('operations.pin-codes.overview') }}"
        @class([
            'inline-flex items-center justify-center rounded-mom-md px-5 py-2.5 text-xs font-semibold uppercase tracking-widest shadow-mom-inner transition-all duration-320 ease-premium',
            'border border-mom-gold text-mom-gold' => $overviewActive,
            'border border-[rgba(255,255,255,0.045)] bg-[rgba(255,255,255,0.03)] text-[var(--text-secondary)] hover:border-[rgba(212,169,95,0.16)] hover:text-[var(--text-primary)]' => ! $overviewActive,
        ])
    >{{ __('Overview') }}</a>
    <a
        href="{{ route('operations.pin-codes.directory') }}"
        @class([
            'inline-flex items-center justify-center rounded-mom-md px-5 py-2.5 text-xs font-semibold uppercase tracking-widest shadow-mom-inner transition-all duration-320 ease-premium',
            'border border-mom-gold text-mom-gold' => $directoryActive,
            'border border-[rgba(255,255,255,0.045)] bg-[rgba(255,255,255,0.03)] text-[var(--text-secondary)] hover:border-[rgba(212,169,95,0.16)] hover:text-[var(--text-primary)]' => ! $directoryActive,
        ])
    >{{ __('Directory') }}</a>
    @can('import', \App\Models\PinCode::class)
        <a
            href="{{ route('operations.pin-codes.bulk-import') }}"
            @class([
                'inline-flex items-center justify-center rounded-mom-md px-5 py-2.5 text-xs font-semibold uppercase tracking-widest shadow-mom-inner transition-all duration-320 ease-premium',
                'border border-mom-gold text-mom-gold' => $bulkImportActive,
                'border border-[rgba(255,255,255,0.045)] bg-[rgba(255,255,255,0.03)] text-[var(--text-secondary)] hover:border-[rgba(212,169,95,0.16)] hover:text-[var(--text-primary)]' => ! $bulkImportActive,
            ])
        >{{ __('Bulk import') }}</a>
    @endcan
</nav>
