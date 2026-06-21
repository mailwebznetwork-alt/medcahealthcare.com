@php
    use App\Support\ProductCategoryContext;

    $pincodes = $service->pincodes ?? collect();
    $isProductCategory = ProductCategoryContext::isService($service);
@endphp
@if ($pincodes->isNotEmpty())
    <x-public.full-bleed class="bg-slate-50 pt-10 pb-4 md:pt-12 md:pb-6">
        <x-public.content-shell>
            <x-public.areas-served-grid
                :areas="$pincodes"
                :service="$service"
                :subtitle="$isProductCategory ? __('India neighbourhoods where this product is available.') : null"
            />
        </x-public.content-shell>
    </x-public.full-bleed>
@endif
