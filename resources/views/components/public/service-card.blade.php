@props([
    'service',
    'headingTag' => 'h3',
    'showPrice' => false,
    'showCategories' => false,
    'showCta' => true,
    'productCategory' => false,
])

@php
    use App\Models\Service;
    use App\Services\Public\PublicDisplayNameResolver;
    use App\Support\ProductCategoryContext;

    if (! $service instanceof Service) {
        return;
    }

    $displayNames = app(PublicDisplayNameResolver::class);
    $url = route('public.services.show', $service->service_code);
    $headline = $displayNames->serviceHeadline($service);
    if ($productCategory) {
        $headline = ProductCategoryContext::stripServicesLabel($headline);
    }
@endphp

<a
    href="{{ $url }}"
    {{ $attributes->class([
        'group flex h-full flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-medca-primary/40 hover:shadow-md',
    ]) }}
>
    <{{ $headingTag }} class="text-lg font-semibold text-slate-900 group-hover:text-medca-primary">{{ $headline }}</{{ $headingTag }}>
    @if (filled($service->short_summary))
        <p class="mt-2 line-clamp-3 flex-1 text-sm text-slate-600">{{ $service->short_summary }}</p>
    @endif
    @if ($showPrice && filled($service->price_range))
        <p class="mt-3 text-sm font-medium text-medca-primary">{{ $service->price_range }}</p>
    @endif
    @if ($showCategories && $service->relationLoaded('categories') && $service->categories->isNotEmpty())
        <div class="mt-3 flex flex-wrap gap-1">
            @foreach ($service->categories as $cat)
                <span class="text-[10px] uppercase tracking-wide text-slate-500">{{ $cat->name }}</span>
            @endforeach
        </div>
    @endif
    @if ($showCta)
        <span class="mt-4 text-sm font-semibold text-medca-primary">{{ $productCategory ? __('View product') : __('View service') }} →</span>
    @endif
</a>
