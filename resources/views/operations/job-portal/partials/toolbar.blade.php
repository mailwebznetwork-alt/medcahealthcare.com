@php
    $manageVacanciesActive = request()->routeIs(
        'operations.job-portal.vacancies.index',
        'operations.job-portal.vacancies.show',
        'operations.job-portal.vacancies.edit',
    );
    $reviewApplicationsActive = request()->routeIs('operations.job-portal.applications.*');
@endphp

<nav class="flex flex-wrap gap-3" aria-label="{{ __('Job portal') }}">
    <a href="{{ route('operations.job-portal.vacancies.create') }}" class="mom-toolbar-pill mom-toolbar-pill-gold">{{ __('Create vacancy') }}</a>
    <a
        href="{{ route('operations.job-portal.vacancies.index') }}"
        @class([
            'mom-toolbar-pill',
            'mom-toolbar-pill-active' => $manageVacanciesActive,
            'mom-toolbar-pill-muted' => ! $manageVacanciesActive,
        ])
    >{{ __('Manage vacancies') }}</a>
    <a
        href="{{ route('operations.job-portal.applications.index') }}"
        @class([
            'mom-toolbar-pill',
            'mom-toolbar-pill-active' => $reviewApplicationsActive,
            'mom-toolbar-pill-muted' => ! $reviewApplicationsActive,
        ])
    >{{ __('Review applications') }}</a>
</nav>
