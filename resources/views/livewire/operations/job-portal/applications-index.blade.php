<div>
    @if (session('status'))
        <div class="mom-card mb-6 border border-[rgba(197,160,89,0.22)] bg-[rgba(197,160,89,0.06)] px-4 py-3 text-sm text-[var(--text-secondary)]" role="status">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mom-card mb-6 border border-[rgba(226,92,92,0.35)] bg-[rgba(226,92,92,0.08)] px-4 py-3 text-sm text-[var(--danger)]" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-8 flex flex-wrap gap-3">
        <select wire:model.live="vacancyId" class="min-w-[14rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
            <option value="">{{ __('All vacancies') }}</option>
            @foreach ($vacancies as $v)
                <option value="{{ $v->id }}">{{ $v->title }}</option>
            @endforeach
        </select>
        <select wire:model.live="pipelineStatus" class="rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
            <option value="">{{ __('All stages') }}</option>
            @foreach (\App\Enums\ApplicationPipelineStatus::cases() as $st)
                <option value="{{ $st->value }}">{{ $st->label() }}</option>
            @endforeach
        </select>
        <x-text-input wire:model.live.debounce.300ms="q" type="search" class="min-w-[12rem]" placeholder="{{ __('Name, email, phone…') }}" variant="mom" />
    </div>

    @if ($applications->isNotEmpty())
        <div class="mb-3 flex flex-wrap gap-2 text-xs">
            <button type="button" wire:click="selectAllVisibleRows({{ $applications->pluck('id')->values()->toJson() }})" class="text-mom-gold hover:underline">{{ __('Select all visible') }}</button>
            <button type="button" wire:click="selectAllFilteredRows" class="text-mom-gold hover:underline">{{ __('Select all filtered results') }}</button>
            <button type="button" wire:click="deselectAllRows" class="text-[var(--text-muted)] hover:underline">{{ __('Deselect all') }}</button>
        </div>

        <x-bulk.selection-toolbar
            :count="$this->bulkSelectedCount()"
            :actions="['modify']"
            :labels="['modify' => __('Open selected')]"
        />
    @endif

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
                            <th class="w-10 px-4 py-3">
                                <input
                                    type="checkbox"
                                    class="rounded border-[var(--border-panel-soft)]"
                                    aria-label="{{ __('Select all visible') }}"
                                    wire:click="selectAllVisibleRows({{ $applications->pluck('id')->values()->toJson() }})"
                                />
                            </th>
                            <th class="px-4 py-3 font-medium">{{ __('Candidate') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Role') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Stage') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Source') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Applied') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ __('View') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                        @foreach ($applications as $application)
                            <tr wire:key="application-row-{{ $application->id }}">
                                <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        class="rounded border-[var(--border-panel-soft)]"
                                        wire:click="toggleBulkRow({{ $application->id }})"
                                        @checked($this->isBulkRowSelected($application->id))
                                        aria-label="{{ __('Select row') }}"
                                    />
                                </td>
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
            <div class="mom-backend-hairline-t px-4 py-3">
                {{ $applications->links() }}
            </div>
        @endif
    </div>

    <x-bulk.action-modal :open="$bulkModalOpen" :preview="$bulkGovernancePreview" />
</div>
