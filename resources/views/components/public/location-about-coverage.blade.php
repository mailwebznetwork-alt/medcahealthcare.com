@props([
    'pin',
    'intro' => null,
])

@php
    use App\Models\PinCode;

    if (config('medca.hide_location_coverage_panel', true)) {
        return;
    }

    if (! $pin instanceof PinCode) {
        return;
    }

    $pin->loadMissing(['landmarks', 'hospitals', 'nearbyAreas']);
    $area = $pin->area_name ?: $pin->locality ?: $pin->city ?: $pin->pincode;
    $body = filled($pin->coverage_text) ? $pin->coverage_text : $intro;
@endphp

@if (filled($body) || $pin->nearbyAreas->isNotEmpty() || $pin->hospitals->isNotEmpty() || $pin->landmarks->isNotEmpty())
    <section {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-slate-50 p-6']) }}>
        <h2 class="text-lg font-semibold text-slate-900 md:text-xl">{{ __('About :area healthcare career consultancy coverage', ['area' => $area]) }}</h2>

        @if (filled($body))
            <p class="mt-2 text-sm leading-relaxed text-slate-600 md:text-base">{{ $body }}</p>
        @endif

        @if ($pin->nearbyAreas->isNotEmpty())
            <div class="mt-4 space-y-2">
                <h3 class="text-sm font-semibold text-slate-800">{{ __('Supported countries and states') }}</h3>
                <ul class="flex flex-wrap gap-2">
                    @foreach ($pin->nearbyAreas as $nearby)
                        <li class="rounded-full bg-white px-3 py-1 text-sm font-medium text-slate-700 ring-1 ring-slate-200">{{ $nearby->area_name }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($pin->hospitals->isNotEmpty())
            <div class="mt-4 space-y-2">
                <h3 class="text-sm font-semibold text-slate-800">{{ __('Nearby healthcare career consultancy facilities') }}</h3>
                <ul class="grid gap-3 md:grid-cols-2">
                    @foreach ($pin->hospitals as $hospital)
                        <li class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <p class="font-semibold text-slate-900">{{ $hospital->name }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($pin->landmarks->isNotEmpty())
            <div class="mt-4 space-y-2">
                <h3 class="text-sm font-semibold text-slate-800">{{ __('Nearby landmarks') }}</h3>
                <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($pin->landmarks as $landmark)
                        <li class="rounded-xl border border-slate-200 bg-white p-4">
                            <p class="font-medium text-slate-900">{{ $landmark->name }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (filled($pin->emergency_coverage_text))
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <h3 class="text-sm font-semibold text-amber-900">{{ __('Emergency coverage') }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-amber-950">{{ $pin->emergency_coverage_text }}</p>
            </div>
        @endif
    </section>
@endif
