@php
    $category = $category ?? null;
    if (! $category instanceof \App\Models\ServiceCategory) {
        return;
    }

    $category->loadMissing(['services.pincodes']);
    $areas = $category->services
        ->flatMap(fn (\App\Models\Service $service) => $service->pincodes)
        ->unique('id')
        ->sortBy('area_name')
        ->values();
@endphp

@if ($areas->isNotEmpty())
    <x-public.section>
        <x-public.areas-served-grid
            :areas="$areas"
            :category="$category"
            :title="__('Areas we cover')"
            :subtitle="__('Bangalore neighbourhoods where :category services are available.', ['category' => $category->name])"
        />
    </x-public.section>
@endif
