@props([
    'service' => null,
    'eyebrow' => null,
    'headline' => null,
    'subheadline' => null,
    'body' => null,
    'headingTag' => 'h1',
    'showCoverage' => true,
    'showPrice' => true,
    'showActions' => true,
    'showBody' => false,
    'variant' => 'page',
    'tone' => 'brand',
])

@php
    use App\Models\Service;

    if ($service instanceof Service) {
        $service->loadMissing(['seo', 'pincodes']);
        $eyebrow = $eyebrow ?? __('Service');
        $headline = $headline ?? (string) ($service->seo?->h1 ?: $service->title);
        $subheadline = $subheadline ?? (string) ($service->short_summary ?? '');
        $body = $body ?? (filled($service->description) ? $service->description : null);
        $coverageCount = $showCoverage ? ($service->pincodes?->count() ?? 0) : 0;
        $priceRange = $showPrice && $service->hasPriceRange() ? $service->price_range : null;
    } else {
        $eyebrow = $eyebrow ?? __('Service');
        $headline = $headline ?? '';
        $subheadline = $subheadline ?? null;
        $coverageCount = 0;
        $priceRange = null;
    }

    $onBrand = $tone === 'brand';
    $shellClass = $variant === 'page'
        ? 'medca-full-bleed w-full py-12 md:py-16'
        : 'rounded-xl px-5 py-8 md:px-8 md:py-10';

    if ($onBrand) {
        $shellClass .= ' medca-hero-gradient text-white';
    } else {
        $shellClass .= ' border-b border-slate-200 bg-white';
    }
@endphp

<div {{ $attributes->class([$shellClass]) }}>
    <div @class(['mx-auto w-full max-w-6xl px-4 md:px-6 lg:px-8' => $variant === 'page'])>
        <div class="medca-page-hero medca-service-page-hero space-y-5" data-page-hero="service">
            @if (filled($eyebrow))
                <p @class([
                    'text-xs font-semibold uppercase tracking-[0.2em]',
                    'text-white/80' => $onBrand,
                    'medca-eyebrow text-slate-500' => ! $onBrand,
                ])>{{ $eyebrow }}</p>
            @endif

            @if (filled($headline))
                <{{ $headingTag }} @class([
                    'text-3xl font-semibold leading-tight tracking-tight md:text-4xl',
                    'text-white' => $onBrand,
                    'text-slate-900' => ! $onBrand,
                ])>{{ $headline }}</{{ $headingTag }}>
            @endif

            @if (filled($subheadline))
                <p @class([
                    'medca-text-body-lg max-w-3xl',
                    'text-white/85' => $onBrand,
                    'text-slate-600' => ! $onBrand,
                ])>{{ $subheadline }}</p>
            @endif

            @if ($coverageCount > 0)
                <p @class([
                    'text-sm font-semibold',
                    'text-white' => $onBrand,
                    'text-slate-900' => ! $onBrand,
                ])>{{ __(':count coverage areas', ['count' => $coverageCount]) }}</p>
            @endif

            @if (filled($priceRange))
                <p @class([
                    'inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-medium md:text-base',
                    'bg-white/15 text-white ring-1 ring-white/25' => $onBrand,
                    'bg-slate-100 text-slate-700 ring-1 ring-slate-200' => ! $onBrand,
                ])>
                    <span class="font-semibold">{{ __('Pricing') }}:</span> {{ $priceRange }}
                </p>
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
