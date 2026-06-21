@props([
    'heading' => null,
    'text' => null,
])

@if (filled($text))
    <section {{ $attributes->class(['medca-svc-detail-section']) }}>
        <h2 class="medca-svc-detail-heading">{{ $heading ?? __('Why choose Medca Consultancy') }}</h2>
        <div class="medca-service-prose prose prose-slate max-w-none prose-p:text-slate-700">
            {!! nl2br(e($text)) !!}
        </div>
    </section>
@endif
