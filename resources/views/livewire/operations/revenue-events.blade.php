<div class="space-y-6">
    <div class="flex flex-wrap items-end gap-4">
        <div class="min-w-[12rem] flex-1">
            <label class="mom-micro">{{ __('Search') }}</label>
            <input type="search" wire:model.live.debounce.300ms="search" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" placeholder="{{ __('Label or notes…') }}" />
        </div>
        <button type="button" wire:click="openCreate" class="ml-auto rounded-lg bg-[rgba(197,160,89,0.12)] px-4 py-2 text-sm font-medium text-mom-gold ring-1 ring-[rgba(197,160,89,0.35)]">{{ __('Record revenue') }}</button>
    </div>

    <div class="mom-card overflow-x-auto p-0">
        <table class="min-w-[900px] w-full text-left text-sm">
            <thead class="border-b border-[color:var(--border-tabstrip-divider)] bg-[rgba(255,255,255,0.02)] mom-micro">
                <tr>
                    <th class="px-4 py-3">{{ __('Recorded') }}</th>
                    <th class="px-4 py-3">{{ __('Amount') }}</th>
                    <th class="px-4 py-3">{{ __('Service') }}</th>
                    <th class="px-4 py-3">{{ __('Pincode') }}</th>
                    <th class="px-4 py-3">{{ __('Label') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($events as $row)
                    <tr wire:key="rev-{{ $row->id }}" class="border-b border-[color:var(--border-tabstrip-divider)]">
                        <td class="px-4 py-3 text-[var(--text-muted)]">{{ $row->recorded_at->format('M j, Y') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $row->currency }} {{ number_format((float) $row->amount, 2) }}</td>
                        <td class="px-4 py-3 text-[var(--text-secondary)]">{{ $row->service?->title ?? '—' }}</td>
                        <td class="px-4 py-3 text-[var(--text-secondary)]">{{ $row->pinCode?->pincode ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $row->label ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="openEdit({{ $row->id }})" class="text-mom-gold hover:underline">{{ __('Edit') }}</button>
                            <button type="button" wire:click="deleteEvent({{ $row->id }})" wire:confirm="{{ __('Delete this revenue entry?') }}" class="ml-2 text-[var(--danger)] hover:underline">{{ __('Delete') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-[var(--text-muted)]">{{ __('No revenue recorded yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $events->links() }}

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="custom-scrollbar max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-[var(--border-panel-soft)] bg-[#0a0f1c] p-6 shadow-xl">
                <h3 class="text-lg font-semibold">{{ $editingId ? __('Edit revenue') : __('Record revenue') }}</h3>
                <form wire:submit="save" class="mt-4 space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mom-micro">{{ __('Amount') }}</label>
                            <input type="number" step="0.01" min="0" wire:model="amount" class="mt-1 w-full rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" required />
                        </div>
                        <div>
                            <label class="mom-micro">{{ __('Currency') }}</label>
                            <input type="text" wire:model="currency" class="mt-1 w-full rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" maxlength="3" />
                        </div>
                    </div>
                    <div>
                        <label class="mom-micro">{{ __('Recorded at') }}</label>
                        <input type="datetime-local" wire:model="recorded_at" class="mt-1 w-full rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" required />
                    </div>
                    <div>
                        <label class="mom-micro">{{ __('Admission') }}</label>
                        <select wire:model="admission_id" class="mt-1 w-full rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                            <option value="">{{ __('—') }}</option>
                            @foreach ($admissions as $adm)
                                <option value="{{ $adm->id }}">{{ $adm->patient_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mom-micro">{{ __('Lead') }}</label>
                        <select wire:model="lead_id" class="mt-1 w-full rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                            <option value="">{{ __('—') }}</option>
                            @foreach ($leads as $lead)
                                <option value="{{ $lead->id }}">{{ $lead->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mom-micro">{{ __('Service') }}</label>
                            <select wire:model="service_id" class="mt-1 w-full rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                                <option value="">{{ __('—') }}</option>
                                @foreach ($services as $svc)
                                    <option value="{{ $svc->id }}">{{ $svc->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mom-micro">{{ __('Category') }}</label>
                            <select wire:model="service_category_id" class="mt-1 w-full rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                                <option value="">{{ __('—') }}</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mom-micro">{{ __('Label') }}</label>
                        <input type="text" wire:model="label" class="mt-1 w-full rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mom-micro">{{ __('Notes') }}</label>
                        <textarea wire:model="notes" rows="2" class="mt-1 w-full rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showModal', false)" class="rounded px-4 py-2 text-sm">{{ __('Cancel') }}</button>
                        <button type="submit" class="rounded-lg bg-[rgba(197,160,89,0.12)] px-4 py-2 text-sm font-medium text-mom-gold ring-1 ring-[rgba(197,160,89,0.35)]">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
