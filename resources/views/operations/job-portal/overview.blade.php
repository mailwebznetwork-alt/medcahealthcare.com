<x-operations.workspace>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['label' => __('Total vacancies'), 'value' => number_format($metrics['total_vacancies']), 'hint' => __('All records in the system')],
            ['label' => __('Active vacancies'), 'value' => number_format($metrics['active_vacancies']), 'hint' => __('Published, active, within closing window')],
            ['label' => __('New applications'), 'value' => number_format($metrics['new_applications']), 'hint' => __('Last 7 days')],
            ['label' => __('WhatsApp applies'), 'value' => number_format($metrics['whatsapp_applies']), 'hint' => __('Attributed or click-tracked')],
            ['label' => __('Published jobs'), 'value' => number_format($metrics['published_jobs']), 'hint' => __('All published postings')],
        ] as $card)
            <article class="mom-card px-5 py-4">
                <p class="mom-micro">{{ $card['label'] }}</p>
                <p class="mom-metric mt-2 leading-none">{{ $card['value'] }}</p>
                <p class="mom-subtext mt-2">{{ $card['hint'] }}</p>
            </article>
        @endforeach
    </div>

    <div class="mt-10 flex flex-wrap gap-3">
        <a
            href="{{ route('operations.job-portal.vacancies.create') }}"
            class="inline-flex items-center justify-center rounded-mom-md border border-[rgba(212,169,95,0.28)] bg-[linear-gradient(180deg,rgba(212,169,95,0.22),rgba(212,169,95,0.12))] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-[#0a0a0a] shadow-[0_0_24px_rgba(212,169,95,0.15)] transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.4)] hover:shadow-[0_0_32px_rgba(212,169,95,0.22)]"
        >{{ __('Create vacancy') }}</a>
        <a
            href="{{ route('operations.job-portal.vacancies.index') }}"
            class="inline-flex items-center justify-center rounded-mom-md border border-[rgba(255,255,255,0.045)] bg-[rgba(255,255,255,0.03)] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-[var(--text-secondary)] shadow-mom-inner transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.16)] hover:text-[var(--text-primary)]"
        >{{ __('Manage vacancies') }}</a>
        <a
            href="{{ route('operations.job-portal.applications.index') }}"
            class="inline-flex items-center justify-center rounded-mom-md border border-[rgba(255,255,255,0.045)] bg-[rgba(255,255,255,0.03)] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-[var(--text-secondary)] shadow-mom-inner transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.16)] hover:text-[var(--text-primary)]"
        >{{ __('Review applications') }}</a>
    </div>

    <div class="mt-12 grid grid-cols-1 gap-8 lg:grid-cols-2">
        <div class="mom-card overflow-hidden p-0">
            <div class="border-b border-[rgba(255,255,255,0.045)] px-5 py-4">
                <h3 class="mom-section-title text-base">{{ __('Recent vacancies') }}</h3>
                <p class="mom-subtext mt-1">{{ __('Latest updates across your hiring catalog.') }}</p>
            </div>
            @if ($recentVacancies->isEmpty())
                <p class="mom-body-text p-6 text-[var(--text-muted)]">{{ __('No vacancies yet.') }}</p>
            @else
                <ul class="divide-y divide-[rgba(255,255,255,0.045)]" role="list">
                    @foreach ($recentVacancies as $vacancy)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-[13px]">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-[var(--text-primary)]">{{ $vacancy->title }}</p>
                                <p class="mom-micro mt-0.5">{{ $vacancy->city }} · {{ $vacancy->workflow_status->label() }}</p>
                            </div>
                            <a href="{{ route('operations.job-portal.vacancies.edit', $vacancy) }}" class="shrink-0 text-mom-gold hover:underline">{{ __('Open') }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="mom-card overflow-hidden p-0">
            <div class="border-b border-[rgba(255,255,255,0.045)] px-5 py-4">
                <h3 class="mom-section-title text-base">{{ __('Recent applications') }}</h3>
                <p class="mom-subtext mt-1">{{ __('Newest candidates entering the pipeline.') }}</p>
            </div>
            @if ($recentApplications->isEmpty())
                <p class="mom-body-text p-6 text-[var(--text-muted)]">{{ __('No applications yet.') }}</p>
            @else
                <ul class="divide-y divide-[rgba(255,255,255,0.045)]" role="list">
                    @foreach ($recentApplications as $application)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-[13px]">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-[var(--text-primary)]">{{ $application->full_name }}</p>
                                <p class="mom-micro mt-0.5">
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
