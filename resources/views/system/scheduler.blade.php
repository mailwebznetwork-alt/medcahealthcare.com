<x-system.shell active-section="scheduler" :welcome-line="__('Scheduled tasks registered for this application.')">
    <article class="mom-card p-6">
        <h2 class="mom-section-title">{{ __('Scheduler') }}</h2>
        <p class="mom-subtext mt-1 mb-4">{{ __('Cron should run') }} <code class="rounded bg-[rgba(0,0,0,0.25)] px-1 py-0.5 text-xs">php artisan schedule:run</code> {{ __('every minute in production.') }}</p>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[36rem] text-left text-[13px]">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                    <tr>
                        <th class="px-4 py-3">{{ __('Command / job') }}</th>
                        <th class="px-4 py-3">{{ __('Schedule') }}</th>
                        <th class="px-4 py-3">{{ __('Notes') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                    @foreach ($scheduledTasks as $task)
                        <tr>
                            <td class="px-4 py-3 font-mono text-[12px] text-[var(--text-primary)]">{{ $task['command'] }}</td>
                            <td class="px-4 py-3 font-mono">{{ $task['expression'] }}</td>
                            <td class="px-4 py-3">{{ $task['description'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </article>
</x-system.shell>
