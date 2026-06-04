@props(['title', 'rows'])

<div>
    <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-[var(--text-primary)]">{{ $title }}</h3>
    <div class="overflow-x-auto rounded-lg border border-[var(--border-panel-soft)]">
        <table class="w-full text-left text-sm">
            <thead class="bg-[var(--bg-elevated)]">
                <tr class="text-[var(--text-secondary)]">
                    <th class="px-3 py-2 font-medium">{{ __('Element') }}</th>
                    <th class="px-3 py-2 font-medium">{{ __('Font family') }}</th>
                    <th class="px-3 py-2 font-medium">{{ __('Font size') }}</th>
                    <th class="px-3 py-2 font-medium">{{ __('Weight') }}</th>
                    <th class="px-3 py-2 font-medium">{{ __('Line height') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="border-t border-[var(--border-fade)]">
                        <td class="px-3 py-2 font-medium text-[var(--text-primary)]">{{ $row['label'] }}</td>
                        <td class="px-3 py-2 text-[var(--text-secondary)]">{{ $row['family'] }}</td>
                        <td class="px-3 py-2 text-[var(--text-secondary)]">{{ $row['size'] }}</td>
                        <td class="px-3 py-2 text-[var(--text-secondary)]">{{ $row['weight'] }}</td>
                        <td class="px-3 py-2 text-[var(--text-secondary)]">{{ $row['line_height'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
