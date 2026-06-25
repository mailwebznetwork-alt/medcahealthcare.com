@props([
    'eyebrow' => null,
    'headline',
    'subline' => null,
    'headingTag' => 'h2',
    'headingClass' => null,
    'tone' => 'brand',
])

@php
    $onBrand = $tone === 'brand';
    $headingClass = $headingClass ?? ($onBrand
        ? 'text-2xl font-semibold text-white md:text-3xl'
        : 'text-2xl font-semibold text-slate-900 md:text-3xl');
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-start justify-between gap-4']) }}>
    <div class="max-w-3xl space-y-3">
        @if (filled($eyebrow))
            <p @class([
                'text-xs font-semibold uppercase tracking-[0.2em]',
                'text-white/80' => $onBrand,
                'tracking-widest text-medca-primary' => ! $onBrand,
            ])>{{ $eyebrow }}</p>
        @endif
        <{{ $headingTag }} @class([$headingClass])>{{ $headline }}</{{ $headingTag }}>
        @if (filled($subline))
            <p @class([
                'text-sm md:text-base',
                'text-white/85' => $onBrand,
                'text-slate-600' => ! $onBrand,
            ])>{{ $subline }}</p>
        @endif
    </div>
</div>
