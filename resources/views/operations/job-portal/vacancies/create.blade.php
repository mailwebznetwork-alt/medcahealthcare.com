<x-operations.workspace>
    <h2 class="mom-section-title mb-8">{{ __('Create vacancy') }}</h2>
    <form method="post" action="{{ route('operations.job-portal.vacancies.store') }}" class="space-y-8">
        @csrf
        @include('operations.job-portal.vacancies._form')
        <div class="flex flex-wrap gap-3">
            <x-primary-button variant="mom">{{ __('Save vacancy') }}</x-primary-button>
            <a href="{{ route('operations.job-portal.vacancies.index') }}" class="mom-cta-ghost">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-operations.workspace>
