@props([
    'eyebrow' => null,
    'headline',
    'subline' => null,
    'intro' => null,
    'body' => null,
    'headingTag' => 'h1',
    'headingClass' => null,
    'showCountry' => true,
    'showActions' => true,
    'showBody' => true,
    'variant' => 'page',
    'tone' => 'brand',
])

@php
    $onBrand = $tone === 'brand';
    $shellClass = $variant === 'page'
        ? 'medca-full-bleed w-full py-12 md:py-16'
        : 'rounded-xl px-5 py-8 md:px-8 md:py-10';

    if ($onBrand) {
        $shellClass .= ' medca-hero-gradient text-white';
    } else {
        $shellClass .= ' border-b border-slate-200 bg-white';
    }

    $headingClass = $headingClass ?? ($onBrand
        ? 'text-3xl font-semibold text-white md:text-4xl'
        : 'text-3xl font-semibold text-slate-900 md:text-4xl');
@endphp

<div {{ $attributes->class([$shellClass]) }}>
    <div @class(['mx-auto w-full max-w-6xl px-4 md:px-6 lg:px-8' => $variant === 'page'])>
        <div class="medca-page-hero medca-location-page-hero space-y-5" data-page-hero="location">
            @if ($showCountry)
                <x-public.location-heading-with-pincode
                    :eyebrow="$eyebrow ?? __('Service Areas')"
                    :headline="$headline"
                    :subline="$subline"
                    :heading-tag="$headingTag"
                    :heading-class="$headingClass"
                    :tone="$tone"
                />
            @else
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
            @endif

            @if (filled($intro))
                <p @class([
                    'medca-text-body-lg max-w-3xl',
                    'text-white/85' => $onBrand,
                    'text-slate-600' => ! $onBrand,
                ])>{{ $intro }}</p>
            @endif

            @if ($showActions)
                <x-public.lead-action-bar :tone="$onBrand ? 'brand' : 'light'" />
            @endif

            @if ($showBody && filled($body))
                <div @class([
                    'border-t pt-8',
                    'border-white/20' => $onBrand,
                    'border-slate-200' => ! $onBrand,
                ])>
                    <div @class([
                        'medca-service-prose prose max-w-none',
                        'prose-invert prose-headings:text-white prose-p:text-white/85' => $onBrand,
                        'prose-slate prose-headings:text-slate-900 prose-p:text-slate-700' => ! $onBrand,
                    ])>
                        {!! $body !!}
                    </div>
                </div>
            @endif

            @isset($slot)
                {{ $slot }}
            @endisset
        </div>
    </div>
</div>
