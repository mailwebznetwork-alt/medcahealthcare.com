@php
    /** @var \App\Models\Service $service */
    $subServices = $subServices ?? collect();
@endphp

<section class="mom-card p-6">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h3 class="mom-section-title mb-2">{{ __('Sub-services') }}</h3>
            <p class="mom-subtext max-w-3xl">
                {{ __('Child offerings under this service. Each sub-service gets its own public page and discovery entry after save.') }}
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('operations.services.sub-services.create', $service) }}" class="mom-cta-primary">{{ __('Add sub-service') }}</a>
            <a href="{{ route('operations.services.sub-services.index', $service) }}" class="mom-cta-ghost">{{ __('Manage all') }}</a>
        </div>
    </div>

    @if ($subServices->isEmpty())
        <p class="mom-body-text text-[var(--text-muted)]">{{ __('No sub-services linked to this service yet.') }}</p>
    @else
        <div class="overflow-x-auto">
            <table class="mom-table min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-[color:var(--border-tabstrip-divider)] text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">
                        <th class="px-3 py-2">{{ __('Title') }}</th>
                        <th class="px-3 py-2">{{ __('Code') }}</th>
                        <th class="px-3 py-2">{{ __('Publish') }}</th>
                        <th class="px-3 py-2">{{ __('Active') }}</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)]">
                    @foreach ($subServices as $sub)
                        <tr>
                            <td class="px-3 py-3 text-[var(--text-primary)]">{{ $sub->title }}</td>
                            <td class="px-3 py-3 font-mono text-xs text-[var(--text-secondary)]">{{ $sub->sub_service_code }}</td>
                            <td class="px-3 py-3 text-[var(--text-secondary)]">{{ $sub->publish_status?->value }}</td>
                            <td class="px-3 py-3 text-[var(--text-secondary)]">{{ $sub->is_active ? __('Yes') : __('No') }}</td>
                            <td class="px-3 py-3 text-right">
                                <a href="{{ route('operations.services.sub-services.edit', [$service, $sub]) }}" class="font-semibold text-mom-gold hover:underline">{{ __('Edit') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
