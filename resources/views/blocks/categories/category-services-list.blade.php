@php
    use App\Support\ProductCategoryContext;

    $services = $categoryServices ?? $services ?? collect();
    $featured = $featuredServices ?? collect();
    $topRated = $topRatedServices ?? collect();
    $category = $category ?? ($serviceCategory ?? null);
    $isProductCategory = ProductCategoryContext::isCategory($category);
@endphp
<x-public.section>
    @if ($featured->isNotEmpty())
        <div class="mb-10">
            <h2 class="text-xl font-semibold text-slate-900">{{ $isProductCategory ? __('Featured') : __('Featured services') }}</h2>
            <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured as $service)
                    <li>
                        <x-public.service-card :service="$service" :product-category="$isProductCategory" />
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
    @if ($topRated->isNotEmpty())
        <div class="mb-10">
            <h2 class="text-xl font-semibold text-slate-900">{{ __('Top rated') }}</h2>
            <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($topRated as $service)
                    <li>
                        <x-public.service-card :service="$service" :product-category="$isProductCategory" />
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
    @if ($services->isNotEmpty())
        <h2 class="text-xl font-semibold text-slate-900">{{ $isProductCategory ? __('In this category') : __('Services in this category') }}</h2>
        <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($services as $service)
                <li>
                    <x-public.service-card :service="$service" :product-category="$isProductCategory" />
                </li>
            @endforeach
        </ul>
    @endif
</x-public.section>
