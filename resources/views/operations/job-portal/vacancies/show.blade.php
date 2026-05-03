<x-app-layout
    :page-title="$vacancy->title"
    :welcome-line="__('Vacancy record.')"
>
    <div class="mom-card p-6">
        <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <dt class="mom-micro">{{ __('Workflow') }}</dt>
                <dd class="mom-body-text mt-1">{{ $vacancy->workflow_status->label() }}</dd>
            </div>
            <div>
                <dt class="mom-micro">{{ __('Visibility') }}</dt>
                <dd class="mom-body-text mt-1">{{ $vacancy->visibility->label() }}</dd>
            </div>
            <div>
                <dt class="mom-micro">{{ __('Applications') }}</dt>
                <dd class="mom-body-text mt-1">{{ $vacancy->applications()->count() }}</dd>
            </div>
        </dl>
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('operations.job-portal.vacancies.edit', $vacancy) }}" class="text-mom-gold hover:underline">{{ __('Edit vacancy') }}</a>
        </div>
    </div>
</x-app-layout>
