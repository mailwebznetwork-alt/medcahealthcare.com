@php
    /** @var \App\Models\Service $service */
    $s = $service;
    $procedures = is_array($s->procedures) ? array_values(array_filter($s->procedures)) : [];
    $specialized = is_array($s->specialized_care) ? array_values(array_filter($s->specialized_care)) : [];
    $shifts = is_array($s->shifts) ? array_values(array_filter($s->shifts)) : [];
@endphp

<style>
    .medca-detail-carousel { width: 100%; padding: 3rem 1rem; background: #f8fafc; font-family: inherit; }
    .medca-detail-carousel-wrap { max-width: 75rem; margin: 0 auto; }
    .medca-detail-carousel-header { margin-bottom: 2rem; text-align: center; }
    .medca-detail-carousel-header h1 {
        margin: 0;
        font-size: clamp(1.75rem, 4vw, 2.5rem);
        font-weight: 700;
        color: #001f5c;
    }
    .medca-detail-carousel-lead { margin: 0.75rem 0 0; font-size: 1.05rem; color: #475569; line-height: 1.6; }
    .medca-detail-track {
        display: flex;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        gap: 1.5rem;
        padding: 1rem 0.5rem;
        scrollbar-width: none;
    }
    .medca-detail-track::-webkit-scrollbar { display: none; }
    .medca-detail-card {
        flex: 0 0 85%;
        scroll-snap-align: start;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
    }
    @media (min-width: 640px) { .medca-detail-card { flex: 0 0 48%; } }
    @media (min-width: 1024px) { .medca-detail-card { flex: 0 0 31%; } }
    .medca-detail-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background: #e8f0fe;
        color: #164081;
        font-size: 0.75rem;
        font-weight: 700;
        border-radius: 999px;
        text-transform: uppercase;
        margin-bottom: 1rem;
    }
    .medca-detail-card h3 { margin: 0 0 0.75rem; font-size: 1.2rem; font-weight: 700; color: #001f5c; }
    .medca-detail-list { list-style: none; padding: 0; margin: 0 0 1.5rem; flex: 1; }
    .medca-detail-list li {
        font-size: 0.88rem;
        color: #334155;
        margin-bottom: 0.55rem;
        display: flex;
        gap: 0.5rem;
        line-height: 1.4;
    }
    .medca-detail-list li::before { content: "✓"; color: #83b735; font-weight: 700; }
    .medca-detail-action {
        display: block;
        text-align: center;
        padding: 0.65rem;
        background: #001f5c;
        color: #fff;
        font-size: 0.88rem;
        font-weight: 600;
        text-decoration: none;
        border-radius: 0.5rem;
    }
    .medca-detail-action:hover { background: #164081; }
</style>

<section class="medca-detail-carousel" data-service-detail="{{ $s->service_code }}">
    <div class="medca-detail-carousel-wrap">
        <header class="medca-detail-carousel-header">
            <h1>{{ $s->seo?->h1 ?: $s->title }}</h1>
            @if (filled($s->short_summary))
                <p class="medca-detail-carousel-lead">{{ $s->short_summary }}</p>
            @endif
        </header>

        <div class="medca-detail-track">
            <article class="medca-detail-card">
                <span class="medca-detail-badge">{{ __('Nursing services') }}</span>
                <h3>{{ __('Procedures included') }}</h3>
                <ul class="medca-detail-list">
                    @forelse ($procedures as $item)
                        <li>{{ $item }}</li>
                    @empty
                        <li>{{ __('Injection & IV care') }}</li>
                        <li>{{ __('Catheter care') }}</li>
                        <li>{{ __('Wound dressing') }}</li>
                        <li>{{ __('Vitals monitoring') }}</li>
                    @endforelse
                </ul>
                <a href="{{ url('/contact') }}" class="medca-detail-action">{{ __('Book procedure') }}</a>
            </article>

            <article class="medca-detail-card">
                <span class="medca-detail-badge">{{ __('Specialized care') }}</span>
                <h3>{{ __('Conditions handled') }}</h3>
                <ul class="medca-detail-list">
                    @forelse ($specialized as $item)
                        <li>{{ $item }}</li>
                    @empty
                        <li>{{ __('Post-surgery care') }}</li>
                        <li>{{ __('ICU setup assistance') }}</li>
                        <li>{{ __('Stroke patient care') }}</li>
                        <li>{{ __('Palliative care') }}</li>
                    @endforelse
                </ul>
                <a href="{{ url('/contact') }}" class="medca-detail-action">{{ __('Request special care') }}</a>
            </article>

            <article class="medca-detail-card">
                <span class="medca-detail-badge">{{ __('Shift availability') }}</span>
                <h3>{{ __('Service timing') }}</h3>
                <ul class="medca-detail-list">
                    @forelse ($shifts as $item)
                        <li>{{ $item }}</li>
                    @empty
                        <li>{{ __('12 hours day duty') }}</li>
                        <li>{{ __('12 hours night duty') }}</li>
                        <li>{{ __('24 hours live-in nursing') }}</li>
                    @endforelse
                    <li>{{ __('Doctor coordination support') }}</li>
                </ul>
                <a href="{{ url('/contact') }}" class="medca-detail-action">{{ __('Check availability') }}</a>
            </article>

            @if ($s->faqs->isNotEmpty())
                <article class="medca-detail-card">
                    <span class="medca-detail-badge">{{ __('Quick help') }}</span>
                    <h3>{{ __('FAQs') }}</h3>
                    <ul class="medca-detail-list">
                        @foreach ($s->faqs->take(4) as $faq)
                            @if (filled(trim((string) $faq->question)))
                                <li><strong>{{ $faq->question }}:</strong> {{ \Illuminate\Support\Str::limit(strip_tags((string) $faq->answer), 70) }}</li>
                            @endif
                        @endforeach
                    </ul>
                    <a href="{{ url('/contact') }}" class="medca-detail-action">{{ __('Ask an advisor') }}</a>
                </article>
            @endif
        </div>
    </div>
</section>
