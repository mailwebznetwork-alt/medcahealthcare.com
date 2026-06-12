@php
    use App\Services\Public\PublicDisplayNameResolver;

    /** @var \Illuminate\Support\Collection<int, \App\Models\Service>|\Illuminate\Support\Enumerable<int, \App\Models\Service> $services */
    $sectionTitle = $sectionTitle ?? null;
    $displayNames = app(PublicDisplayNameResolver::class);
@endphp

@if ($services->isNotEmpty())
<x-public.full-bleed class="bg-slate-50 py-10 md:py-12" data-layout="services-carousel">
    <x-public.content-shell>
        <div class="medca-svc-carousel">
        @if (filled($sectionTitle))
            <header class="medca-svc-carousel-header">
                <h2>{{ $sectionTitle }}</h2>
            </header>
        @endif

            <div class="medca-svc-carousel-track" role="list">
                @foreach ($services as $service)
                    <a
                        href="{{ route('public.services.show', $service->service_code) }}"
                        class="medca-svc-carousel-card"
                        role="listitem"
                    >
                        <x-public.catalog-list-card-image :model="$service" />
                        <span class="medca-svc-carousel-card__body">
                            <h3>{{ $displayNames->serviceHeadline($service) }}</h3>
                            <x-public.catalog-card-summary :model="$service" :limit="120" />
                            <span class="cta">{{ __('View service →') }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </x-public.content-shell>
</x-public.full-bleed>
@endif
