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
        class="mom-cta-primary"
    >{{ __('Create vacancy') }}</a>
    <a
        href="{{ route('operations.job-portal.vacancies.index') }}"
        @class([
            'mom-cta-ghost',
            'mom-cta-ghost--active' => $manageVacanciesActive,
        ])
    >{{ __('Manage vacancies') }}</a>
    <a
        href="{{ route('operations.job-portal.applications.index') }}"
        @class([
            'mom-cta-ghost',
            'mom-cta-ghost--active' => $reviewApplicationsActive,
        ])
    >{{ __('Review applications') }}</a>
</nav>
