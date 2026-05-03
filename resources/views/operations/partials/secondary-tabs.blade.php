@if (request()->routeIs('operations.job-portal.*'))
    @php
        $tabs = [
            ['label' => __('Overview'), 'route' => 'operations.job-portal.overview', 'active' => request()->routeIs('operations.job-portal.overview')],
            ['label' => __('Vacancies'), 'route' => 'operations.job-portal.vacancies.index', 'active' => request()->routeIs('operations.job-portal.vacancies.*')],
            ['label' => __('Applications'), 'route' => 'operations.job-portal.applications.index', 'active' => request()->routeIs('operations.job-portal.applications.*')],
        ];
    @endphp
    <nav class="flex flex-wrap gap-0 border-b border-[rgba(255,255,255,0.045)]" aria-label="{{ __('Job Portal') }}">
        @foreach ($tabs as $tab)
            <a
                href="{{ route($tab['route']) }}"
                @class([
                    'inline-flex items-center border-b-2 px-4 py-2.5 text-xs font-semibold uppercase tracking-widest transition-colors duration-320 ease-premium',
                    'border-mom-gold text-mom-gold' => $tab['active'],
                    'border-transparent text-[var(--text-muted)] hover:border-[rgba(255,255,255,0.08)] hover:text-[var(--text-secondary)]' => ! $tab['active'],
                ])
            >{{ $tab['label'] }}</a>
        @endforeach
    </nav>
@elseif (request()->routeIs('operations.pin-codes.*'))
    @php
        $tabs = [
            ['label' => __('Overview'), 'route' => 'operations.pin-codes.overview', 'active' => request()->routeIs('operations.pin-codes.overview')],
            ['label' => __('Directory'), 'route' => 'operations.pin-codes.directory', 'active' => request()->routeIs('operations.pin-codes.directory', 'operations.pin-codes.create', 'operations.pin-codes.edit')],
            ['label' => __('Bulk Import'), 'route' => 'operations.pin-codes.bulk-import', 'active' => request()->routeIs('operations.pin-codes.bulk-import', 'operations.pin-codes.bulk-import.preview', 'operations.pin-codes.bulk-import.confirm', 'operations.pin-codes.bulk-import.cancel')],
        ];
    @endphp
    <nav class="flex flex-wrap gap-0 border-b border-[rgba(255,255,255,0.045)]" aria-label="{{ __('Pin Codes') }}">
        @foreach ($tabs as $tab)
            <a
                href="{{ route($tab['route']) }}"
                @class([
                    'inline-flex items-center border-b-2 px-4 py-2.5 text-xs font-semibold uppercase tracking-widest transition-colors duration-320 ease-premium',
                    'border-mom-gold text-mom-gold' => $tab['active'],
                    'border-transparent text-[var(--text-muted)] hover:border-[rgba(255,255,255,0.08)] hover:text-[var(--text-secondary)]' => ! $tab['active'],
                ])
            >{{ $tab['label'] }}</a>
        @endforeach
    </nav>
@endif
