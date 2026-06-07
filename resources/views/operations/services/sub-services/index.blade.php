<x-operations.workspace>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('operations.services.edit', ['service' => $service, 'tab' => 'sub_services']) }}" class="mom-cta-ghost">{{ __('Back to :service', ['service' => $service->title]) }}</a>
        <a href="{{ route('operations.services.sub-services.create', $service) }}" class="mom-cta-primary">{{ __('Add sub-service') }}</a>
    </div>

    <h2 class="mom-section-title mb-2">{{ __('Sub-services for :service', ['service' => $service->title]) }}</h2>
    <p class="mom-subtext mb-8">{{ __('Manage child offerings under parent service :code.', ['code' => $service->service_code]) }}</p>

    <div class="mom-card overflow-hidden p-0">
        @if ($subServices->isEmpty())
            <p class="mom-body-text p-6 text-[var(--text-muted)]">{{ __('No sub-services yet. Add one manually or import via services.xlsx.') }}</p>
        @else
            <div class="mom-table overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-[13px]">
                    <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                        <tr>
                            <th class="px-4 py-3">{{ __('Title') }}</th>
                            <th class="px-4 py-3">{{ __('Code') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                            <th class="px-4 py-3">{{ __('Active') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                        @foreach ($subServices as $sub)
                            <tr>
                                <td class="px-4 py-3 font-medium text-[var(--text-primary)]">{{ $sub->title }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $sub->sub_service_code }}</td>
                                <td class="px-4 py-3">{{ $sub->publish_status?->value }}</td>
                                <td class="px-4 py-3">{{ $sub->is_active ? __('Yes') : __('No') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('operations.services.sub-services.edit', [$service, $sub]) }}" class="font-semibold text-mom-gold hover:underline">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-operations.workspace>
