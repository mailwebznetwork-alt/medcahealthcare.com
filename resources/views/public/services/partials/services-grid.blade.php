@php
    use App\Services\Public\PublicDisplayNameResolver;

    /** @var \Illuminate\Support\Collection<int, \App\Models\Service>|\Illuminate\Support\Enumerable<int, \App\Models\Service> $services */
    $sectionTitle = $sectionTitle ?? null;
    $displayNames = app(PublicDisplayNameResolver::class);
@endphp

<x-public.full-bleed class="bg-slate-100 py-10 md:py-12" data-layout="services-grid">
    <x-public.content-shell>
        <div class="medca-svc-grid-section">
        @if (filled($sectionTitle))
            <h2>{{ $sectionTitle }}</h2>
        @endif

        @if ($services->isEmpty())
            <p class="medca-svc-grid-empty">{{ __('No services selected for this block.') }}</p>
        @else
            <ul class="medca-svc-grid">
                @foreach ($services as $service)
                    <li>
                        <a href="{{ route('public.services.show', $service->service_code) }}" class="medca-svc-grid-card">
                            <h3>{{ $displayNames->serviceHeadline($service) }}</h3>
                            @if (filled($service->short_summary))
                                <p>{{ \Illuminate\Support\Str::limit(strip_tags($service->short_summary), 160) }}</p>
                            @endif
                            <span class="link">{{ __('Learn more →') }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
        </div>
    </x-public.content-shell>
</x-public.full-bleed>
