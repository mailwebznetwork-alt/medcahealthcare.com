<section class="mom-card p-6">
    <h2 class="mom-section-title">{{ __('War Room') }}</h2>
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <article class="mom-card p-5">
            <p class="mom-micro">{{ __('Pending Intercepts') }}</p>
            <p class="mom-metric mt-2 leading-none">{{ number_format((int) ($warRoomDashboard['pending_intercepts'] ?? 0)) }}</p>
        </article>
        <article class="mom-card p-5">
            <p class="mom-micro">{{ __('In Progress') }}</p>
            <p class="mom-metric mt-2 leading-none">{{ number_format((int) ($warRoomDashboard['in_progress_intercepts'] ?? 0)) }}</p>
        </article>
        <article class="mom-card p-5">
            <p class="mom-micro">{{ __('Completed Intercepts') }}</p>
            <p class="mom-metric mt-2 leading-none">{{ number_format((int) ($warRoomDashboard['completed_intercepts'] ?? 0)) }}</p>
        </article>
    </div>

    <form method="post" action="{{ route('growth-center.war-room.intercept.store') }}" class="mt-6 grid grid-cols-1 gap-3 xl:grid-cols-5">
        @csrf
        <input type="text" name="keyword" placeholder="{{ __('Keyword') }}" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
        <input type="text" name="gap_type" placeholder="{{ __('Gap type') }}" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
        <select name="competitor_id" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
            <option value="">{{ __('Competitor (optional)') }}</option>
            @foreach ($allCompetitors as $competitor)
                <option value="{{ $competitor->id }}">{{ $competitor->name }}</option>
            @endforeach
        </select>
        <select name="priority" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
            <option value="high">High</option>
            <option value="medium" selected>Medium</option>
            <option value="low">Low</option>
        </select>
        <input type="text" name="action" placeholder="{{ __('Action') }}" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
        <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Create Intercept') }}</button>
    </form>
</section>

<section class="mom-card mt-8 p-6">
    <h3 class="mom-section-title">{{ __('Intercepts') }}</h3>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[44rem] text-left text-[13px]">
            <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('Keyword') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Competitor') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Gap Type') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Action') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Priority') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                @forelse ($intercepts as $intercept)
                    <tr>
                        <td class="px-4 py-3 text-[var(--text-primary)]">{{ $intercept->keyword }}</td>
                        <td class="px-4 py-3">{{ $intercept->competitor?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $intercept->gap_type }}</td>
                        <td class="px-4 py-3">{{ $intercept->action }}</td>
                        <td class="px-4 py-3">{{ $intercept->priority }}</td>
                        <td class="px-4 py-3">{{ $intercept->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-[var(--text-muted)]">{{ __('No intercepts found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
