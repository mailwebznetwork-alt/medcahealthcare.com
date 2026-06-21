<div class="space-y-6">
    <div class="flex flex-wrap items-end gap-4">
        <div class="min-w-[12rem] flex-1">
            <label class="mom-micro">{{ __('Search') }}</label>
            <input type="search" wire:model.live.debounce.300ms="search" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" placeholder="{{ __('Patient name or phone…') }}" />
        </div>
        <div>
            <label class="mom-micro">{{ __('Status') }}</label>
            <select wire:model.live="filterStatus" class="mt-1 min-w-[10rem] rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                <option value="">{{ __('All') }}</option>
                @foreach ($statuses as $st)
                    <option value="{{ $st->value }}">{{ $st->label() }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" wire:click="openCreate" class="ml-auto rounded-lg bg-[rgba(197,160,89,0.12)] px-4 py-2 text-sm font-medium text-mom-gold ring-1 ring-[rgba(197,160,89,0.35)]">{{ __('Record admission') }}</button>
    </div>

    <div class="mom-card overflow-x-auto p-0">
        <table class="min-w-[900px] w-full text-left text-sm">
            <thead class="border-b border-[color:var(--border-tabstrip-divider)] bg-[rgba(255,255,255,0.02)] mom-micro">
                <tr>
                    <th class="px-4 py-3">{{ __('Patient') }}</th>
                    <th class="px-4 py-3">{{ __('Service') }}</th>
                    <th class="px-4 py-3">{{ __('Country') }}</th>
                    <th class="px-4 py-3">{{ __('Status') }}</th>
                    <th class="px-4 py-3">{{ __('Admitted') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($admissions as $row)
                    <tr wire:key="adm-{{ $row->id }}" class="border-b border-[color:var(--border-tabstrip-divider)]">
                        <td class="px-4 py-3 font-medium">{{ $row->patient_name }}</td>
                        <td class="px-4 py-3 text-[var(--text-secondary)]">{{ $row->service?->title ?? '—' }}</td>
                        <td class="px-4 py-3 text-[var(--text-secondary)]">{{ $row->pinCode?->pincode ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $row->status->label() }}</td>
                        <td class="px-4 py-3 text-[var(--text-muted)]">{{ $row->admitted_at?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="openEdit({{ $row->id }})" class="text-mom-gold hover:underline">{{ __('Edit') }}</button>
                            <button type="button" wire:click="deleteAdmission({{ $row->id }})" wire:confirm="{{ __('Delete this admission?') }}" class="ml-2 text-[var(--danger)] hover:underline">{{ __('Delete') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-[var(--text-muted)]">{{ __('No admissions recorded yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $admissions->links() }}

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="custom-scrollbar max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-[var(--border-panel-soft)] bg-[#0a0f1c] p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-[var(--text-primary)]">{{ $editingId ? __('Edit admission') : __('Record admission') }}</h3>
                <form wire:submit="save" class="mt-4 space-y-3">
                    <div>
                        <label class="mom-micro">{{ __('Patient name') }}</label>
                        <input type="text" wire:model="patient_name" class="mt-1 w-full rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" required />
                    </div>
                    <div>
                        <label class="mom-micro">{{ __('Phone') }}</label>
                        <input type="tel" wire:model="patient_phone" class="mt-1 w-full rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mom-micro">{{ __('Linked lead') }}</label>
                        <select wire:model="lead_id" class="mt-1 w-full rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                            <option value="">{{ __('—') }}</option>
                            @foreach ($leads as $lead)
                                <option value="{{ $lead->id }}">{{ $lead->name }} ({{ $lead->phone }})</option>
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
                            <label class="mom-micro">{{ __('Country') }}</label>
                            <select wire:model="pin_code_id" class="mt-1 w-full rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                                <option value="">{{ __('—') }}</option>
                                @foreach ($pincodes as $pin)
                                    <option value="{{ $pin->id }}">{{ $pin->pincode }} {{ $pin->area_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mom-micro">{{ __('Status') }}</label>
                        <select wire:model="status" class="mt-1 w-full rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                            @foreach ($statuses as $st)
                                <option value="{{ $st->value }}">{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mom-micro">{{ __('Admitted at') }}</label>
                            <input type="datetime-local" wire:model="admitted_at" class="mt-1 w-full rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="mom-micro">{{ __('Discharged at') }}</label>
                            <input type="datetime-local" wire:model="discharged_at" class="mt-1 w-full rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="mom-micro">{{ __('Notes') }}</label>
                        <textarea wire:model="notes" rows="3" class="mt-1 w-full rounded border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showModal', false)" class="rounded px-4 py-2 text-sm text-[var(--text-secondary)]">{{ __('Cancel') }}</button>
                        <button type="submit" class="rounded-lg bg-[rgba(197,160,89,0.12)] px-4 py-2 text-sm font-medium text-mom-gold ring-1 ring-[rgba(197,160,89,0.35)]">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
