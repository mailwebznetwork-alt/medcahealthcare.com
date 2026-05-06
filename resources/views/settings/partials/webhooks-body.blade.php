<section class="mom-card p-6">
    <h2 class="mom-section-title">{{ __('Outbound webhook events') }}</h2>
    <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ __('Configure the Webhook integration with endpoint URL and secret; when enabled, these events POST JSON to your receiver.') }}</p>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[44rem] text-left text-[13px]">
            <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('Event key') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('When it fires') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                @foreach ($webhookEvents as $row)
                    <tr>
                        <td class="px-4 py-3 font-mono text-[12px]">{{ $row['key'] }}</td>
                        <td class="px-4 py-3">{{ __($row['description']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
