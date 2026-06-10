@php
    use App\Services\Public\PublicDisplayNameResolver;

    /** @var \App\Models\Service $service */
    $s = $service;
    $displayNames = app(PublicDisplayNameResolver::class);
    $procedures = is_array($s->procedures) ? array_values(array_filter($s->procedures)) : [];
    $specialized = is_array($s->specialized_care) ? array_values(array_filter($s->specialized_care)) : [];
    $shifts = is_array($s->shifts) ? array_values(array_filter($s->shifts)) : [];
@endphp

<x-public.full-bleed class="bg-slate-50 py-10 md:py-12" data-service-detail="{{ $s->service_code }}">
    <x-public.content-shell>
        <div class="medca-detail-carousel">
        <header class="medca-detail-carousel-header">
            <h1>{{ $displayNames->serviceHeadline($s) }}</h1>
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
    </x-public.content-shell>
</x-public.full-bleed>
