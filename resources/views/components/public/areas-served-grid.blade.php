@props([
    'areas' => collect(),
    'service' => null,
    'initial' => 8,
    'title' => __('Areas we cover'),
    'subtitle' => __('Bangalore neighbourhoods where this service is available.'),
])

@php
    use App\Models\Service;
    use App\Services\Public\PinCodeCoverageUrlResolver;

    $areas = $areas instanceof \Illuminate\Support\Collection ? $areas : collect($areas);
    $initial = max(1, (int) $initial);
    $serviceModel = $service instanceof Service ? $service : null;
    $urlResolver = app(PinCodeCoverageUrlResolver::class);
    $urls = $urlResolver->urlsFor($areas, $serviceModel);
@endphp

@if ($areas->isNotEmpty())
    <section {{ $attributes->merge(['class' => 'medca-areas-served space-y-4']) }} x-data="{ expanded: false }">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 md:text-xl">{{ $title }}</h2>
            @if (filled($subtitle))
                <p class="mt-1 text-sm text-slate-600 md:text-base">{{ $subtitle }}</p>
            @endif
        </div>
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($areas as $index => $pc)
                @php
                    $url = $urls[$pc->id] ?? route('location.pincode.select', ['pincode' => $pc->pincode]);
                    $areaLabel = $pc->area_name ?: $pc->locality ?: $pc->city ?: $pc->pincode;
                @endphp
                <li
                    x-show="expanded || {{ $index }} < {{ $initial }}"
                    x-cloak
                    class="group"
                >
                    <a href="{{ $url }}" class="flex h-full min-w-0 flex-col gap-1 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-medca-primary/40 hover:shadow-md">
                        <span class="font-mono text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $pc->pincode }}</span>
                        <span class="text-sm font-semibold text-slate-900 group-hover:text-medca-primary">{{ $areaLabel }}</span>
                        @if (filled($pc->city))
                            <span class="text-xs text-slate-500">{{ $pc->city }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
        @if ($areas->count() > $initial)
            <button
                type="button"
                @click="expanded = !expanded"
                class="text-sm font-semibold text-medca-primary underline underline-offset-2"
                x-text="expanded ? '{{ __('Show less') }}' : '{{ __('View more areas (:count)', ['count' => $areas->count() - $initial]) }}'"
            ></button>
        @endif
    </section>
@endif
