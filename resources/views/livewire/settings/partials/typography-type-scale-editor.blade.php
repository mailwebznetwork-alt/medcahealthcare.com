@props(['breakpoint', 'title', 'labels'])

<div class="rounded-lg border border-[var(--border-panel-soft)] bg-[var(--bg-surface)] p-4">
    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-[var(--text-primary)]">{{ $title }}</h3>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="bg-[var(--bg-elevated)] text-[var(--text-secondary)]">
                <tr>
                    <th class="px-2 py-2 font-medium">{{ __('Element') }}</th>
                    <th class="px-2 py-2 font-medium">{{ __('Size (rem)') }}</th>
                    <th class="px-2 py-2 font-medium">{{ __('Weight') }}</th>
                    <th class="px-2 py-2 font-medium">{{ __('Line height') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($labels as $key => $label)
                    <tr class="border-t border-[var(--border-fade)]">
                        <td class="px-2 py-2 font-medium text-[var(--text-primary)]">{{ $label }}</td>
                        <td class="px-2 py-2">
                            <input
                                type="number"
                                step="0.0125"
                                min="0.5"
                                max="6"
                                wire:model="typography.type_scale.{{ $key }}.{{ $breakpoint }}.size"
                                class="mom-input w-full min-w-[5rem]"
                            />
                        </td>
                        <td class="px-2 py-2">
                            <input
                                type="number"
                                step="100"
                                min="100"
                                max="900"
                                wire:model="typography.type_scale.{{ $key }}.{{ $breakpoint }}.weight"
                                class="mom-input w-full min-w-[4.5rem]"
                            />
                        </td>
                        <td class="px-2 py-2">
                            <input
                                type="number"
                                step="0.01"
                                min="1"
                                max="2.5"
                                wire:model="typography.type_scale.{{ $key }}.{{ $breakpoint }}.line_height"
                                class="mom-input w-full min-w-[4.5rem]"
                            />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
