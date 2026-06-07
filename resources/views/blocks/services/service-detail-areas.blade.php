@php
    $pincodes = $service->pincodes ?? collect();
@endphp
@if ($pincodes->isNotEmpty())
    <x-public.section class="bg-slate-50">
        <x-public.areas-served-grid :areas="$pincodes" :service="$service" />
    </x-public.section>
@endif
