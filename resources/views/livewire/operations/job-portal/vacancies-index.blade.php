<div>
    @if (session('status') === 'vacancy-deleted')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Vacancy removed.') }}</p>
    @elseif (session('status'))
        <div class="mom-card mb-6 border border-[rgba(197,160,89,0.22)] bg-[rgba(197,160,89,0.06)] px-4 py-3 text-sm text-[var(--text-secondary)]" role="status">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mom-card mb-6 border border-[rgba(226,92,92,0.35)] bg-[rgba(226,92,92,0.08)] px-4 py-3 text-sm text-[var(--danger)]" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="flex flex-1 flex-wrap gap-3">
            <x-text-input
                wire:model.live.debounce.300ms="q"
                type="search"
                class="min-w-[12rem] flex-1"
                placeholder="{{ __('Search title, department, city, country…') }}"
                variant="mom"
            />
            <select wire:model.live="workflowStatus" class="rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (\App\Enums\VacancyWorkflowStatus::cases() as $st)
                    <option value="{{ $st->value }}">{{ $st->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="visibility" class="rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('All visibility') }}</option>
                @foreach (\App\Enums\VacancyVisibility::cases() as $vis)
                    <option value="{{ $vis->value }}">{{ $vis->label() }}</option>
                @endforeach
            </select>
        </div>
        <a href="{{ route('operations.job-portal.vacancies.create') }}" class="mom-cta-primary">{{ __('New vacancy') }}</a>
    </div>

    @if ($vacancies->isNotEmpty())
        <div class="mb-3 mt-6 flex flex-wrap gap-2 text-xs">
            <button type="button" wire:click="selectAllVisibleRows({{ $vacancies->pluck('id')->values()->toJson() }})" class="text-mom-gold hover:underline">{{ __('Select all visible') }}</button>
            <button type="button" wire:click="selectAllFilteredRows" class="text-mom-gold hover:underline">{{ __('Select all filtered results') }}</button>
            <button type="button" wire:click="deselectAllRows" class="text-[var(--text-muted)] hover:underline">{{ __('Deselect all') }}</button>
        </div>

        <x-bulk.selection-toolbar
            :count="$this->bulkSelectedCount()"
            :actions="['modify', 'duplicate', 'delete']"
        />
    @endif

    <div class="mom-card mt-8 overflow-hidden p-0">
        @if ($vacancies->isEmpty())
            <div class="p-10 text-center">
                <p class="mom-section-title">{{ __('No vacancies match your filters') }}</p>
                <p class="mom-subtext mt-2">{{ __('Create a vacancy to start your hiring pipeline.') }}</p>
                <a href="{{ route('operations.job-portal.vacancies.create') }}" class="mom-subtext mt-6 inline-flex text-mom-gold hover:underline">{{ __('New vacancy') }}</a>
            </div>
        @else
            <div class="mom-table overflow-x-auto">
                <table class="w-full min-w-[960px] text-left text-[13px]">
                    <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input
                                    type="checkbox"
                                    class="rounded border-[var(--border-panel-soft)]"
                                    aria-label="{{ __('Select all visible') }}"
                                    wire:click="selectAllVisibleRows({{ $vacancies->pluck('id')->values()->toJson() }})"
                                />
                            </th>
                            <th class="px-4 py-3 font-medium">{{ __('Title') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Location') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Visibility') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                        @foreach ($vacancies as $vacancy)
                            <tr wire:key="vacancy-row-{{ $vacancy->id }}">
                                <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        class="rounded border-[var(--border-panel-soft)]"
                                        wire:click="toggleBulkRow({{ $vacancy->id }})"
                                        @checked($this->isBulkRowSelected($vacancy->id))
                                        aria-label="{{ __('Select row') }}"
                                    />
                                </td>
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
                                    <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
                                        <a href="{{ route('operations.job-portal.vacancies.edit', $vacancy) }}" class="text-mom-gold hover:underline">{{ __('Edit') }}</a>
                                        <form method="post" action="{{ route('operations.job-portal.vacancies.duplicate', $vacancy) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-[var(--text-muted)] hover:text-[var(--text-primary)]">{{ __('Duplicate') }}</button>
                                        </form>
                                        <form method="post" action="{{ route('operations.job-portal.vacancies.destroy', $vacancy) }}" class="inline" onsubmit="return confirm(@js(__('Delete this vacancy and its applications?')));">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-[var(--danger)] hover:underline">{{ __('Delete') }}</button>
                                        </form>
                                    </div>
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

    <x-bulk.action-modal :open="$bulkModalOpen" :preview="$bulkGovernancePreview" />
</div>
