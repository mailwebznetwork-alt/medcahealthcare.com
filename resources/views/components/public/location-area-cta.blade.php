@props([
    'heading' => null,
    'content' => null,
])

@if (! config('medca.hide_location_neighbourhood_cta', true))
<section {{ $attributes->merge(['class' => 'rounded-xl border border-medca-primary/20 bg-medca-primary/5 p-6']) }}>
    @if (filled($heading))
        <h2 class="text-lg font-semibold text-slate-900 md:text-xl">{{ $heading }}</h2>
    @else
        <h2 class="text-lg font-semibold text-slate-900 md:text-xl">{{ __('Book care in your neighbourhood') }}</h2>
    @endif

    @if (filled($content))
        <p class="mt-2 text-sm text-slate-700 md:text-base">{{ $content }}</p>
    @else
        <p class="mt-2 text-sm text-slate-700 md:text-base">{{ __('Speak with Medca coordinators for same-day home healthcare in your area.') }}</p>
    @endif

    <x-public.lead-action-bar class="mt-4" />
</section>
@endif
