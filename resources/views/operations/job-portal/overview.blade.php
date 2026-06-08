<x-operations.workspace>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['label' => __('Total vacancies'), 'value' => number_format($metrics['total_vacancies']), 'hint' => __('All records in the system'), 'href' => \App\Support\AdminMetricLinks::jobPortalVacancies()],
            ['label' => __('Active vacancies'), 'value' => number_format($metrics['active_vacancies']), 'hint' => __('Published, active, within closing window'), 'href' => \App\Support\AdminMetricLinks::jobPortalVacancies('published')],
            ['label' => __('New applications'), 'value' => number_format($metrics['new_applications']), 'hint' => __('Last 7 days'), 'href' => \App\Support\AdminMetricLinks::jobPortalApplications()],
            ['label' => __('WhatsApp applies'), 'value' => number_format($metrics['whatsapp_applies']), 'hint' => __('Attributed or click-tracked'), 'href' => \App\Support\AdminMetricLinks::jobPortalApplications()],
            ['label' => __('Published jobs'), 'value' => number_format($metrics['published_jobs']), 'hint' => __('All published postings'), 'href' => \App\Support\AdminMetricLinks::jobPortalVacancies('published')],
        ] as $card)
            <x-admin.metric-card
                :label="$card['label']"
                :value="$card['value']"
                :hint="$card['hint']"
                :href="$card['href']"
            />
        @endforeach
    </div>

    <div class="mt-12 grid grid-cols-1 gap-8 lg:grid-cols-2">
        <div class="mom-card overflow-hidden p-0">
            <div class="mom-backend-hairline-b px-5 py-4">
                <h3 class="mom-section-title text-base">{{ __('Recent vacancies') }}</h3>
                <p class="mom-subtext mt-1">{{ __('Latest updates across your hiring catalog.') }}</p>
            </div>
            @if ($recentVacancies->isEmpty())
                <p class="mom-body-text p-6 text-[var(--text-muted)]">{{ __('No vacancies yet.') }}</p>
            @else
                <ul class="divide-y divide-[color:var(--border-tabstrip-divider)]" role="list">
                    @foreach ($recentVacancies as $vacancy)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-[13px]">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-[var(--text-primary)]">{{ $vacancy->title }}</p>
                                <p class="mom-subtext mt-0.5 text-[var(--text-muted)]">{{ $vacancy->city }} · {{ $vacancy->workflow_status->label() }}</p>
                            </div>
                            <a href="{{ route('operations.job-portal.vacancies.edit', $vacancy) }}" class="shrink-0 text-mom-gold hover:underline">{{ __('Open') }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="mom-card overflow-hidden p-0">
            <div class="mom-backend-hairline-b px-5 py-4">
                <h3 class="mom-section-title text-base">{{ __('Recent applications') }}</h3>
                <p class="mom-subtext mt-1">{{ __('Newest candidates entering the pipeline.') }}</p>
            </div>
            @if ($recentApplications->isEmpty())
                <p class="mom-body-text p-6 text-[var(--text-muted)]">{{ __('No applications yet.') }}</p>
            @else
                <ul class="divide-y divide-[color:var(--border-tabstrip-divider)]" role="list">
                    @foreach ($recentApplications as $application)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-[13px]">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-[var(--text-primary)]">{{ $application->full_name }}</p>
                                <p class="mom-subtext mt-0.5 text-[var(--text-muted)]">
                                    {{ $application->vacancy?->title ?? __('Unknown role') }} · {{ $application->pipeline_status->label() }}
                                </p>
                            </div>
                            <a href="{{ route('operations.job-portal.applications.show', $application) }}" class="shrink-0 text-mom-gold hover:underline">{{ __('Review') }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-operations.workspace>
