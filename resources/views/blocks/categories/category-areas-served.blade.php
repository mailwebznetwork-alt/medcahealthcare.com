@php
    use App\Support\ProductCategoryContext;

    $category = $category ?? null;
    if (! $category instanceof \App\Models\ServiceCategory) {
        return;
    }

    $isProductCategory = ProductCategoryContext::isCategory($category);

    $areas = $category->pincodes()
        ->where('pin_codes.is_active', true)
        ->orderBy('pin_codes.area_name')
        ->orderBy('pin_codes.country')
        ->get();
@endphp

@if ($areas->isNotEmpty())
    <x-public.section class="!pb-4 md:!pb-6">
        <x-public.areas-served-grid
            :areas="$areas"
            :category="$category"
            :title="__('Countries & States We Serve')"
            :subtitle="$isProductCategory
                ? __('India neighbourhoods where :category is available.', ['category' => $category->name])
                : __('India neighbourhoods where :category services are available.', ['category' => $category->name])"
        />
    </x-public.section>
@endif
