@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Service>|\Illuminate\Support\Enumerable<int, \App\Models\Service> $services */
    $sectionTitle = $sectionTitle ?? null;
@endphp

<style>
    .medca-svc-grid-section {
        width: 100%;
        padding: 2.5rem 1rem 3rem;
        background: #f1f5f9;
    }
    .medca-svc-grid-wrap { max-width: 72rem; margin: 0 auto; }
    .medca-svc-grid-section h2 {
        margin: 0 0 1.25rem;
        font-size: clamp(1.35rem, 3vw, 1.75rem);
        font-weight: 700;
        color: #001f5c;
        text-align: center;
    }
    .medca-svc-grid {
        display: grid;
        gap: 1.25rem;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    @media (min-width: 640px) { .medca-svc-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .medca-svc-grid { grid-template-columns: repeat(3, 1fr); } }
    .medca-svc-grid-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        background: #fff;
        border-radius: 1rem;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .medca-svc-grid-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(0, 31, 92, 0.12);
    }
    .medca-svc-grid-card h3 { margin: 0; font-size: 1.1rem; font-weight: 600; color: #0f172a; }
    .medca-svc-grid-card p {
        margin: 0.65rem 0 0;
        flex: 1;
        font-size: 0.9rem;
        line-height: 1.55;
        color: #475569;
    }
    .medca-svc-grid-card .link {
        margin-top: 1rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: #0046ad;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .medca-svc-grid-empty { text-align: center; color: #64748b; }
</style>

<section class="medca-svc-grid-section" data-layout="services-grid">
    <div class="medca-svc-grid-wrap">
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
                            <h3>{{ $service->title }}</h3>
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
</section>
