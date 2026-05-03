<x-operations.workspace>
    <h2 class="mom-section-title mb-8">{{ __('Applications') }}</h2>
    <form method="get" action="{{ route('operations.job-portal.applications.index') }}" class="mb-8 flex flex-wrap gap-3">
        <select name="vacancy_id" class="min-w-[14rem] rounded-mom-md border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
            <option value="">{{ __('All vacancies') }}</option>
            @foreach ($vacancies as $v)
                <option value="{{ $v->id }}" @selected(request('vacancy_id') == $v->id)>{{ $v->title }}</option>
            @endforeach
        </select>
        <select name="pipeline_status" class="rounded-mom-md border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
            <option value="">{{ __('All stages') }}</option>
            @foreach (\App\Enums\ApplicationPipelineStatus::cases() as $st)
                <option value="{{ $st->value }}" @selected(request('pipeline_status') === $st->value)>{{ $st->label() }}</option>
            @endforeach
        </select>
        <x-text-input name="q" type="search" class="min-w-[12rem]" :value="request('q')" placeholder="{{ __('Name, email, phone…') }}" variant="mom" />
        <x-secondary-button variant="mom" type="submit">{{ __('Filter') }}</x-secondary-button>
    </form>

    <div class="mom-card overflow-hidden p-0">
        @if ($applications->isEmpty())
            <div class="p-10 text-center">
                <p class="mom-section-title">{{ __('No applications yet') }}</p>
                <p class="mom-subtext mt-2">{{ __('Applications appear when candidates submit through the public careers flow.') }}</p>
            </div>
        @else
            <div class="mom-table overflow-x-auto">
                <table class="w-full min-w-[800px] text-left text-[13px]">
                    <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ __('Candidate') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Role') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Stage') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Source') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Applied') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ __('View') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                        @foreach ($applications as $application)
                            <tr>
                                <td class="px-4 py-3 font-medium text-[var(--text-primary)]">{{ $application->full_name }}</td>
                                <td class="px-4 py-3">{{ $application->vacancy?->title }}</td>
                                <td class="px-4 py-3">{{ $application->pipeline_status->label() }}</td>
                                <td class="px-4 py-3">{{ $application->source ?? '—' }}</td>
                                <td class="px-4 py-3 text-[var(--text-muted)]">{{ $application->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('operations.job-portal.applications.show', $application) }}" class="text-mom-gold hover:underline">{{ __('Open') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-[rgba(255,255,255,0.045)] px-4 py-3">
                {{ $applications->links() }}
            </div>
        @endif
    </div>
</x-operations.workspace>
