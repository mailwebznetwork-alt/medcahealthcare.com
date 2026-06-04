@props([
    'tone' => 'light',
    'class' => '',
])

@php
    $toneClass = match ($tone) {
        'dark' => 'bg-slate-900 text-white',
        'muted' => 'bg-slate-50 text-slate-800',
        'brand' => 'medca-hero-gradient text-white',
        default => 'bg-white text-slate-900',
    };
@endphp

<x-public.section {{ $attributes->merge(['class' => trim('py-12 md:py-16 '.$toneClass.' '.$class)]) }}>
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        {{ $slot }}
    </div>
</x-public.section>
