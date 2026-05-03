<x-operations.workspace>
    <h2 class="mom-section-title mb-8">{{ __('Edit vacancy') }}</h2>
    @if (session('status') === 'vacancy-created' || session('status') === 'vacancy-updated' || session('status') === 'vacancy-duplicated')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Vacancy saved.') }}</p>
    @endif

    <form method="post" action="{{ route('operations.job-portal.vacancies.update', $vacancy) }}" class="space-y-8">
        @csrf
        @method('put')
        @include('operations.job-portal.vacancies._form')
        <div class="flex flex-wrap gap-3">
            <x-primary-button variant="mom">{{ __('Update vacancy') }}</x-primary-button>
            <a href="{{ route('operations.job-portal.vacancies.index') }}" class="inline-flex items-center justify-center rounded-mom-md border border-[rgba(255,255,255,0.045)] bg-[rgba(255,255,255,0.03)] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-[var(--text-secondary)] shadow-mom-inner transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.16)] hover:text-[var(--text-primary)]">{{ __('Back to list') }}</a>
        </div>
    </form>

    <div class="mt-10 flex flex-wrap gap-3 border-t border-[rgba(255,255,255,0.045)] pt-8">
        <form method="post" action="{{ route('operations.job-portal.vacancies.duplicate', $vacancy) }}">
            @csrf
            <x-secondary-button variant="mom" type="submit">{{ __('Duplicate as draft') }}</x-secondary-button>
        </form>
        @if ($vacancy->workflow_status === \App\Enums\VacancyWorkflowStatus::Published && $vacancy->visibility === \App\Enums\VacancyVisibility::Public)
            <a href="{{ route('careers.show', ['slug' => $vacancy->slug]) }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-mom-md border border-[rgba(255,255,255,0.045)] bg-[rgba(255,255,255,0.03)] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-[var(--text-secondary)] shadow-mom-inner transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.16)] hover:text-[var(--text-primary)]">
                {{ __('Open public posting') }}
            </a>
        @endif
        <form method="post" action="{{ route('operations.job-portal.vacancies.destroy', $vacancy) }}" onsubmit="return confirm('{{ __('Delete this vacancy and its applications?') }}');">
            @csrf
            @method('delete')
            <x-danger-button variant="mom" type="submit">{{ __('Delete') }}</x-danger-button>
        </form>
    </div>
</x-operations.workspace>
