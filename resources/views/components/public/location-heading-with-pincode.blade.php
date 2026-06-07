@props([
    'eyebrow' => null,
    'headline',
    'subline' => null,
    'headingTag' => 'h2',
    'pincodeButton' => null,
    'headingClass' => null,
    'tone' => 'brand',
])

@php
    $pincodeButton = $pincodeButton ?? __('Change Pincode');
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
    <button
        type="button"
        onclick="window.dispatchEvent(new CustomEvent('open-pincode-modal'))"
        @class([
            'inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold underline underline-offset-2',
            'text-white hover:text-white/90' => $onBrand,
            'text-medca-primary' => ! $onBrand,
        ])
    >
        <svg class="shrink-0" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11a2 2 0 100-4 2 2 0 000 4z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-4.35 7-10a7 7 0 10-14 0c0 5.65 7 10 7 10z" />
        </svg>
        <span>{{ $pincodeButton }}</span>
    </button>
</div>
