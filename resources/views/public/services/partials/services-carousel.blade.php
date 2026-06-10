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
                        <h3>{{ $displayNames->serviceHeadline($service) }}</h3>
                        @if (filled($service->short_summary))
                            <p>{{ \Illuminate\Support\Str::limit(strip_tags($service->short_summary), 120) }}</p>
                        @endif
                        <span class="cta">{{ __('View service →') }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </x-public.content-shell>
</x-public.full-bleed>
@endif
