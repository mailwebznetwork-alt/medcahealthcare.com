@php
    use App\Support\ProductCategoryContext;

    $pincodes = $service->pincodes ?? collect();
    $isProductCategory = ProductCategoryContext::isService($service);
@endphp
@if ($pincodes->isNotEmpty())
    <x-public.section class="bg-slate-50">
        <x-public.areas-served-grid
            :areas="$pincodes"
            :service="$service"
            :subtitle="$isProductCategory ? __('Bangalore neighbourhoods where this product is available.') : null"
        />
    </x-public.section>
@endif
