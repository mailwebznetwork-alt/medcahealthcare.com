@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Service> $subServices */
    $servicesInCategory = $subServices ?? collect();
@endphp

<section class="mom-card p-6">
    <h3 class="mom-section-title mb-2">{{ __('Services in category') }}</h3>
    <p class="mom-subtext mb-6 max-w-3xl">{{ __('Assign services from each service’s Basic tab. This list is read-only here.') }}</p>
    @if ($servicesInCategory->isEmpty())
        <p class="text-sm text-[var(--text-muted)]">{{ __('No services assigned yet.') }}</p>
    @else
        <ul class="divide-y divide-[rgba(255,255,255,0.06)]">
            @foreach ($servicesInCategory as $svc)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <div>
                        <p class="font-medium text-[var(--text-primary)]">{{ $svc->title }}</p>
                        <p class="font-mono text-xs text-[var(--text-muted)]">{{ $svc->service_code }}</p>
                    </div>
                    <a href="{{ route('operations.services.edit', $svc) }}" class="mom-cta-ghost text-xs">{{ __('Edit service') }}</a>
                </li>
            @endforeach
        </ul>
    @endif
</section>
