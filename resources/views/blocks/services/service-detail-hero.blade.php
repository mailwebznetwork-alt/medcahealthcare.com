@php
    use App\Models\Page;
    use App\Support\BlockContent;
    $page = app(\App\Services\Content\ContentRenderContext::class)->all()['currentPage'] ?? null;
    $pageOverrides = ($page instanceof Page && is_array($page->block_overrides_json))
        ? (is_array($page->block_overrides_json['service-detail-hero']['content'] ?? null)
            ? $page->block_overrides_json['service-detail-hero']['content']
            : [])
        : [];
    $pick = static function (string $key, string $fallback) use ($pageOverrides): string {
        $value = $pageOverrides[$key] ?? null;
        if (is_string($value) && trim($value) !== '' && ! BlockContent::isBladePlaceholder(trim($value))) {
            return trim($value);
        }

        return $fallback;
    };
    $blockMedia = is_array($blockMedia ?? null) ? $blockMedia : [];
    $detailHeroImage = \App\Support\BlockMediaUrl::first($blockMedia, 'image', 'desktop_image', 'icon');
    if (! $detailHeroImage && filled($service->featured_image ?? null)) {
        $detailHeroImage = \Illuminate\Support\Str::startsWith($service->featured_image, ['http://', 'https://'])
            ? $service->featured_image
            : asset('storage/'.$service->featured_image);
    }
    $serviceHeadline = (string) ($service->seo?->h1 ?: $service->title);
    $serviceSummary = (string) ($service->short_summary ?? '');
    $eyebrow = $pick('eyebrow', __('Service'));
    $headline = $pick('headline', $serviceHeadline);
    $subheadline = $pick('subheadline', $serviceSummary);
@endphp
<x-public.section>
    <div class="space-y-6" data-service-detail-hero>
        <header class="space-y-4 border-b border-slate-200 pb-6">
            <p class="medca-eyebrow text-slate-500">{{ $eyebrow }}</p>
            <h1 class="text-3xl font-semibold leading-tight tracking-tight text-slate-900 md:text-4xl">{{ $headline }}</h1>
            @if (filled($subheadline))
                <p class="medca-subheadline max-w-3xl text-slate-600">{{ $subheadline }}</p>
            @endif
            @if ($service->hasPriceRange())
                <p class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-slate-200 md:text-base">
                    <span class="font-semibold">{{ __('Pricing') }}:</span> {{ $service->price_range }}
                </p>
            @endif
        </header>

        @if ($detailHeroImage)
            <figure class="overflow-hidden rounded-xl border border-slate-200 shadow-sm">
                <img
                    src="{{ $detailHeroImage }}"
                    alt="{{ $service->image_alt ?? $service->title }}"
                    class="max-h-[28rem] w-full object-cover"
                    loading="lazy"
                    decoding="async"
                />
            </figure>
        @endif

        @if (filled($service->description))
            <div class="medca-service-prose prose prose-slate max-w-none prose-headings:text-slate-900 prose-p:text-slate-700">
                {!! $service->description !!}
            </div>
        @endif
    </div>
</x-public.section>
