<x-operations.workspace>
    <h2 class="mom-section-title mb-8">{{ __('Vacancies') }}</h2>
    @if (session('status') === 'vacancy-deleted')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Vacancy removed.') }}</p>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-4">
        <form method="get" action="{{ route('operations.job-portal.vacancies.index') }}" class="flex flex-1 flex-wrap gap-3">
            <x-text-input name="q" type="search" class="min-w-[12rem] flex-1" :value="request('q')" placeholder="{{ __('Search title, department, city, PIN…') }}" variant="mom" />
            <select name="workflow_status" class="rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (\App\Enums\VacancyWorkflowStatus::cases() as $st)
                    <option value="{{ $st->value }}" @selected(request('workflow_status') === $st->value)>{{ $st->label() }}</option>
                @endforeach
            </select>
            <select name="visibility" class="rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('All visibility') }}</option>
                @foreach (\App\Enums\VacancyVisibility::cases() as $vis)
                    <option value="{{ $vis->value }}" @selected(request('visibility') === $vis->value)>{{ $vis->label() }}</option>
                @endforeach
            </select>
            <x-secondary-button variant="mom" type="submit">{{ __('Filter') }}</x-secondary-button>
        </form>
        <a href="{{ route('operations.job-portal.vacancies.create') }}" class="mom-cta-primary">{{ __('New vacancy') }}</a>
    </div>

    <div class="mom-card mt-8 overflow-hidden p-0">
        @if ($vacancies->isEmpty())
            <div class="p-10 text-center">
                <p class="mom-section-title">{{ __('No vacancies match your filters') }}</p>
                <p class="mom-subtext mt-2">{{ __('Create a vacancy to start your hiring pipeline.') }}</p>
            </div>
        @else
            <div class="mom-table overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-[13px]">
                    <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ __('Title') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Location') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Visibility') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                        @foreach ($vacancies as $vacancy)
                            <tr>
                                <td class="px-4 py-3 font-medium text-[var(--text-primary)]">{{ $vacancy->title }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-[var(--text-secondary)]">{{ $vacancy->city }}</span>
                                    @if ($vacancy->pin_code)
                                        <span class="mom-micro"> · {{ $vacancy->pin_code }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-[var(--text-secondary)]">
                                        {{ $vacancy->workflow_status->label() }}
                                    </span>
                                    @if (! $vacancy->is_active)
                                        <span class="mom-micro ml-1 text-[var(--warning)]">{{ __('Paused') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $vacancy->visibility->label() }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('operations.job-portal.vacancies.edit', $vacancy) }}" class="text-mom-gold hover:underline">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mom-backend-hairline-t px-4 py-3">
                {{ $vacancies->links() }}
            </div>
        @endif
    </div>
</x-operations.workspace>
