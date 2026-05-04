<div class="space-y-6">
    <div class="flex flex-wrap items-end gap-4">
        <div class="min-w-[12rem] flex-1">
            <label class="mom-micro">{{ __('Search') }}</label>
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm"
                placeholder="{{ __('Name, phone, email, service…') }}"
            />
        </div>
        <div>
            <label class="mom-micro">{{ __('Status') }}</label>
            <select wire:model.live="filterStatus" class="mt-1 w-full min-w-[10rem] rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                <option value="">{{ __('All') }}</option>
                @foreach ($statuses as $st)
                    <option value="{{ $st->value }}">{{ $st->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mom-micro">{{ __('Source') }}</label>
            <select wire:model.live="filterSource" class="mt-1 w-full min-w-[10rem] rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                <option value="">{{ __('All') }}</option>
                @foreach ($sources as $src)
                    <option value="{{ $src->value }}">{{ $src->label() }}</option>
                @endforeach
            </select>
        </div>
        <button
            type="button"
            wire:click="openCreate"
            class="ml-auto rounded-lg bg-[rgba(197,160,89,0.12)] px-4 py-2 text-sm font-medium text-mom-gold ring-1 ring-[rgba(197,160,89,0.35)]"
        >{{ __('New lead') }}</button>
    </div>

    <div class="mom-card overflow-x-auto p-0">
        <table class="min-w-[960px] w-full text-left text-sm">
            <thead class="border-b border-[var(--border-panel-soft)] bg-[rgba(255,255,255,0.02)] mom-micro">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Phone') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Service') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Source') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Assigned') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Follow-up') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Created') }}</th>
                    <th class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leads as $lead)
                    <tr wire:key="lead-{{ $lead->id }}" class="border-b border-[var(--border-panel-soft)]">
                        <td class="px-4 py-3 font-medium text-[var(--text-primary)]">{{ $lead->name }}</td>
                        <td class="px-4 py-3 font-mono text-[var(--text-secondary)]">{{ $lead->phone }}</td>
                        <td class="max-w-[140px] truncate px-4 py-3 text-[var(--text-secondary)]" title="{{ $lead->service }}">{{ $lead->service }}</td>
                        <td class="px-4 py-3 text-[var(--text-secondary)]">{{ $lead->source->label() }}</td>
                        <td class="px-4 py-3">
                            <select
                                class="w-full min-w-[8rem] rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-2 py-1 text-xs"
                                wire:change="updateStatus({{ $lead->id }}, $event.target.value)"
                            >
                                @foreach ($statuses as $st)
                                    <option value="{{ $st->value }}" @selected($lead->status->value === $st->value)>{{ $st->label() }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-3">
                            <select
                                class="w-full min-w-[7rem] rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-2 py-1 text-xs"
                                wire:change="updateAssigned({{ $lead->id }}, $event.target.value)"
                            >
                                <option value="">{{ __('—') }}</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}" @selected($lead->assigned_to === $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-3">
                            <input
                                type="date"
                                value="{{ $lead->follow_up_date?->format('Y-m-d') }}"
                                class="w-full min-w-[9rem] rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-2 py-1 text-xs"
                                wire:change="updateFollowUp({{ $lead->id }}, $event.target.value)"
                            />
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-[var(--text-muted)]">{{ $lead->created_at->timezone(config('app.timezone'))->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-wrap justify-end gap-2">
                                <a href="{{ route('operations.bookings.show', $lead) }}" class="text-mom-gold hover:underline">{{ __('View') }}</a>
                                <button type="button" wire:click="openEdit({{ $lead->id }})" class="text-[var(--text-secondary)] hover:text-[var(--text-primary)]">{{ __('Edit') }}</button>
                                <button type="button" wire:click="openNote({{ $lead->id }})" class="text-[var(--text-secondary)] hover:text-[var(--text-primary)]">{{ __('Note') }}</button>
                                <button
                                    type="button"
                                    wire:click="deleteLead({{ $lead->id }})"
                                    wire:confirm="{{ __('Delete this lead?') }}"
                                    class="text-[var(--danger)] hover:underline"
                                >{{ __('Delete') }}</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-[var(--text-muted)]">{{ __('No leads match your filters.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-center">
        {{ $leads->links() }}
    </div>

    @if ($showLeadModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" wire:click.self="$set('showLeadModal', false)">
            <div class="mom-card max-h-[90vh] w-full max-w-lg overflow-y-auto p-6 shadow-mom-elevated custom-scrollbar" @keydown.escape.window="$wire.set('showLeadModal', false)">
                <h3 class="mom-section-title text-base">{{ $editingId ? __('Edit lead') : __('New lead') }}</h3>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="mom-micro">{{ __('Name') }} *</label>
                        <input type="text" wire:model="name" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                        @error('name') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mom-micro">{{ __('Phone') }} *</label>
                        <input type="text" wire:model="phone" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                        @error('phone') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mom-micro">{{ __('Email') }}</label>
                        <input type="email" wire:model="email" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                        @error('email') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mom-micro">{{ __('Service') }} *</label>
                        <input type="text" wire:model="service" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                        @error('service') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mom-micro">{{ __('Message') }}</label>
                        <textarea wire:model="message" rows="3" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm"></textarea>
                        @error('message') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mom-micro">{{ __('Source') }}</label>
                            <select wire:model="source" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                                @foreach ($sources as $src)
                                    <option value="{{ $src->value }}">{{ $src->label() }}</option>
                                @endforeach
                            </select>
                            @error('source') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mom-micro">{{ __('Status') }}</label>
                            <select wire:model="status" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                                @foreach ($statuses as $st)
                                    <option value="{{ $st->value }}">{{ $st->label() }}</option>
                                @endforeach
                            </select>
                            @error('status') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="mom-micro">{{ __('Campaign') }}</label>
                        <input type="text" wire:model="campaign" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mom-micro">{{ __('PIN code') }}</label>
                            <select wire:model="pin_code_id" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                                <option value="">{{ __('—') }}</option>
                                @foreach ($pinCodes as $pc)
                                    <option value="{{ $pc->id }}">{{ $pc->pincode }} @if($pc->area_name) — {{ $pc->area_name }} @endif</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mom-micro">{{ __('Assigned to') }}</label>
                            <select wire:model="assigned_to" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                                <option value="">{{ __('—') }}</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mom-micro">{{ __('Follow-up date') }}</label>
                        <input type="date" wire:model="follow_up_date" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('showLeadModal', false)" class="rounded-lg border border-[var(--border-panel-soft)] px-4 py-2 text-sm">{{ __('Cancel') }}</button>
                    <button type="button" wire:click="saveLead" class="rounded-lg bg-[rgba(197,160,89,0.12)] px-4 py-2 text-sm font-medium text-mom-gold ring-1 ring-[rgba(197,160,89,0.35)]">{{ __('Save') }}</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showNoteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" wire:click.self="$set('showNoteModal', false)">
            <div class="mom-card w-full max-w-md p-6 shadow-mom-elevated">
                <h3 class="mom-section-title text-base">{{ __('Add note') }}</h3>
                <textarea wire:model="noteText" rows="4" class="mt-4 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" placeholder="{{ __('Internal note…') }}"></textarea>
                @error('noteText') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                <div class="mt-4 flex justify-end gap-3">
                    <button type="button" wire:click="$set('showNoteModal', false)" class="rounded-lg border border-[var(--border-panel-soft)] px-4 py-2 text-sm">{{ __('Cancel') }}</button>
                    <button type="button" wire:click="saveNote" class="rounded-lg bg-[rgba(197,160,89,0.12)] px-4 py-2 text-sm font-medium text-mom-gold ring-1 ring-[rgba(197,160,89,0.35)]">{{ __('Save') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
