@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Service>|\Illuminate\Support\Enumerable<int, \App\Models\Service> $services */
    $sectionTitle = $sectionTitle ?? null;
@endphp

<style>
    .medca-svc-carousel {
        width: 100%;
        padding: 2.5rem 1rem 3rem;
        font-family: inherit;
        background-color: #f8fafc;
    }
    .medca-svc-carousel-wrap { max-width: 72rem; margin: 0 auto; }
    .medca-svc-carousel-header { margin-bottom: 1.5rem; text-align: center; }
    .medca-svc-carousel-header h2 {
        margin: 0;
        font-size: clamp(1.35rem, 3vw, 1.75rem);
        font-weight: 700;
        color: #001f5c;
    }
    .medca-svc-carousel-track {
        display: flex;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        scroll-behavior: smooth;
        gap: 1.25rem;
        padding: 0.5rem 0.25rem 1rem;
        scrollbar-width: none;
    }
    .medca-svc-carousel-track::-webkit-scrollbar { display: none; }
    .medca-svc-carousel-card {
        flex: 0 0 85%;
        scroll-snap-align: start;
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 1.25rem;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .medca-svc-carousel-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(0, 31, 92, 0.1);
    }
    @media (min-width: 640px) { .medca-svc-carousel-card { flex: 0 0 48%; } }
    @media (min-width: 1024px) { .medca-svc-carousel-card { flex: 0 0 32%; } }
    .medca-svc-carousel-card h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: #2557a7;
    }
    .medca-svc-carousel-card p {
        margin: 0.5rem 0 0;
        flex: 1;
        font-size: 0.875rem;
        line-height: 1.5;
        color: #475569;
    }
    .medca-svc-carousel-card .cta {
        margin-top: 1rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: #0046ad;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .medca-svc-carousel-empty { text-align: center; color: #64748b; padding: 2rem 1rem; }
</style>

<section class="medca-svc-carousel" data-layout="services-carousel">
    <div class="medca-svc-carousel-wrap">
        @if (filled($sectionTitle))
            <header class="medca-svc-carousel-header">
                <h2>{{ $sectionTitle }}</h2>
            </header>
        @endif

        @if ($services->isEmpty())
            <p class="medca-svc-carousel-empty">{{ __('No services selected for this block.') }}</p>
        @else
            <div class="medca-svc-carousel-track" role="list">
                @foreach ($services as $service)
                    <a
                        href="{{ route('public.services.show', $service->service_code) }}"
                        class="medca-svc-carousel-card"
                        role="listitem"
                    >
                        <h3>{{ $service->title }}</h3>
                        @if (filled($service->short_summary))
                            <p>{{ \Illuminate\Support\Str::limit(strip_tags($service->short_summary), 120) }}</p>
                        @endif
                        <span class="cta">{{ __('View service →') }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
