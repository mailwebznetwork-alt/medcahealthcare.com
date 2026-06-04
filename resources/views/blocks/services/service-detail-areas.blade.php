@php
    $pincodes = $service->pincodes ?? collect();
@endphp
@if ($pincodes->isNotEmpty())
    <x-public.section class="bg-slate-50">
        <div class="medca-service-areas space-y-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 md:text-xl">{{ __('Areas served') }}</h2>
                <p class="mt-1 text-sm text-slate-600 md:text-base">{{ __('Bangalore neighbourhoods where this service is available.') }}</p>
            </div>
            <ul class="medca-service-areas__grid">
                @foreach ($pincodes as $pc)
                    <li class="medca-service-areas__item">
                        <span class="font-mono text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $pc->pincode }}</span>
                        <span class="text-sm font-medium leading-snug text-slate-900 md:text-base">{{ $pc->area_name }}</span>
                        @if (filled($pc->city))
                            <span class="text-xs text-slate-500 md:text-sm">{{ $pc->city }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </x-public.section>
@endif
