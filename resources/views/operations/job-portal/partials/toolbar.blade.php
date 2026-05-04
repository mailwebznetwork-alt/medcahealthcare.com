@php
    $manageVacanciesActive = request()->routeIs(
        'operations.job-portal.vacancies.index',
        'operations.job-portal.vacancies.show',
        'operations.job-portal.vacancies.edit',
    );
    $reviewApplicationsActive = request()->routeIs('operations.job-portal.applications.*');
@endphp

<nav class="flex flex-wrap gap-3" aria-label="{{ __('Job portal') }}">
    <a
        href="{{ route('operations.job-portal.vacancies.create') }}"
        class="inline-flex items-center justify-center rounded-mom-md border border-[rgba(212,169,95,0.28)] bg-[linear-gradient(180deg,rgba(212,169,95,0.22),rgba(212,169,95,0.12))] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-[#0a0a0a] shadow-[0_0_24px_rgba(212,169,95,0.15)] transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.4)] hover:shadow-[0_0_32px_rgba(212,169,95,0.22)]"
    >{{ __('Create vacancy') }}</a>
    <a
        href="{{ route('operations.job-portal.vacancies.index') }}"
        @class([
            'inline-flex items-center justify-center rounded-mom-md px-5 py-2.5 text-xs font-semibold uppercase tracking-widest shadow-mom-inner transition-all duration-320 ease-premium',
            'border border-mom-gold text-mom-gold' => $manageVacanciesActive,
            'border border-[rgba(255,255,255,0.045)] bg-[rgba(255,255,255,0.03)] text-[var(--text-secondary)] hover:border-[rgba(212,169,95,0.16)] hover:text-[var(--text-primary)]' => ! $manageVacanciesActive,
        ])
    >{{ __('Manage vacancies') }}</a>
    <a
        href="{{ route('operations.job-portal.applications.index') }}"
        @class([
            'inline-flex items-center justify-center rounded-mom-md px-5 py-2.5 text-xs font-semibold uppercase tracking-widest shadow-mom-inner transition-all duration-320 ease-premium',
            'border border-mom-gold text-mom-gold' => $reviewApplicationsActive,
            'border border-[rgba(255,255,255,0.045)] bg-[rgba(255,255,255,0.03)] text-[var(--text-secondary)] hover:border-[rgba(212,169,95,0.16)] hover:text-[var(--text-primary)]' => ! $reviewApplicationsActive,
        ])
    >{{ __('Review applications') }}</a>
</nav>
