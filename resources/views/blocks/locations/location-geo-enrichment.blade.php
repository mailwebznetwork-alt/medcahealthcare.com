@php
    use App\Models\PinCode;
    use App\Models\Service;

    $svc = $service ?? null;
    $pin = $serviceLocation?->pincode ?? null;

    if ($svc instanceof Service && $pin === null && isset($serviceLocation)) {
        $pin = $serviceLocation->pincode;
    }

    if (! $pin instanceof PinCode && isset($serviceLocation)) {
        $pin = $serviceLocation->loadMissing('pincode')->pincode;
    }

    if (! $svc instanceof Service || ! $pin instanceof PinCode) {
        return;
    }

    $pin->loadMissing(['landmarks', 'hospitals', 'locationFaqs', 'nearbyAreas']);
    $provisioner = app(\App\Services\Operations\ServiceLocationPageProvisioner::class);
    $templates = app(\App\Services\Operations\ServiceLocationTemplateResolver::class);
    $area = $pin->area_name ?: $pin->locality ?: $pin->city ?: $pin->pincode;
    $title = $provisioner->locationTitle($svc, $pin);
    $intro = $provisioner->localIntro($svc, $pin);
    $h2 = $templates->locationH2($svc, $pin);
    $h3 = $templates->locationH3($svc, $pin);
    $ctaHeading = $templates->ctaHeading($svc, $pin);
    $ctaContent = $templates->ctaContent($svc, $pin);
@endphp

<x-public.location-page-hero
    :eyebrow="__('Near You')"
    :headline="$title"
    :subline="__('Professional healthcare services available in :area (:pin).', ['area' => $area, 'pin' => $pin->pincode])"
    :intro="$intro"
    :show-body="false"
    tone="brand"
/>

<x-public.section>
<div class="medca-location-geo space-y-10" data-location-pincode="{{ $pin->pincode }}">
    <x-public.location-service-detail
        :service="$svc"
        :pin-code="$pin"
        :compact-faqs="false"
        class="border-medca-primary/20 shadow-md"
    />

    @if ($h2)
        <h2 class="text-2xl font-semibold text-slate-900">{{ $h2 }}</h2>
    @endif
    @if ($h3)
        <h3 class="text-xl font-semibold text-slate-800">{{ $h3 }}</h3>
    @endif

    <section class="rounded-xl border border-slate-200 bg-slate-50 p-6">
        <h3 class="text-lg font-semibold text-slate-900">{{ __('About :area healthcare coverage', ['area' => $area]) }}</h3>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">
            {{ filled($pin->coverage_text) ? $pin->coverage_text : $intro }}
        </p>
    </section>

    @if ($pin->nearbyAreas->isNotEmpty())
        <section class="space-y-3">
            <h3 class="text-lg font-semibold text-slate-900">{{ __('Nearby areas') }}</h3>
            <ul class="flex flex-wrap gap-2">
                @foreach ($pin->nearbyAreas as $nearby)
                    <li class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700 ring-1 ring-slate-200">{{ $nearby->area_name }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($pin->hospitals->isNotEmpty())
        <section class="space-y-3">
            <h3 class="text-lg font-semibold text-slate-900">{{ __('Nearby healthcare facilities') }}</h3>
            <ul class="grid gap-3 md:grid-cols-2">
                @foreach ($pin->hospitals as $hospital)
                    <li class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="font-semibold text-slate-900">{{ $hospital->name }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($pin->landmarks->isNotEmpty())
        <section class="space-y-3">
            <h3 class="text-lg font-semibold text-slate-900">{{ __('Nearby landmarks') }}</h3>
            <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($pin->landmarks as $landmark)
                    <li class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="font-medium text-slate-900">{{ $landmark->name }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if (filled($pin->emergency_coverage_text))
        <section class="rounded-xl border border-amber-200 bg-amber-50 p-5">
            <h3 class="text-lg font-semibold text-amber-900">{{ __('Emergency coverage') }}</h3>
            <p class="mt-2 text-sm leading-relaxed text-amber-950">{{ $pin->emergency_coverage_text }}</p>
        </section>
    @endif

    @if ($pin->locationFaqs->isNotEmpty())
        <section class="space-y-4">
            <h3 class="text-lg font-semibold text-slate-900">{{ __('Area-specific questions') }}</h3>
            <dl class="space-y-3">
                @foreach ($pin->locationFaqs as $faq)
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <dt class="font-semibold text-slate-900">{{ $faq->question }}</dt>
                        <dd class="mt-2 text-sm leading-relaxed text-slate-600">{{ $faq->answer }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    @php
        $svc->loadMissing('pincodes');
    @endphp
    @if ($svc->pincodes->count() > 0)
        <x-public.areas-served-grid
            :areas="$svc->pincodes"
            :service="$svc"
            :title="__('Areas served')"
            :subtitle="__('Other neighbourhoods where :service is available.', ['service' => $svc->title])"
        />
    @endif

    @if ($ctaHeading || $ctaContent)
        <section class="rounded-xl border border-medca-primary/20 bg-medca-primary/5 p-6">
            @if ($ctaHeading)
                <h3 class="text-lg font-semibold text-slate-900">{{ $ctaHeading }}</h3>
            @endif
            @if ($ctaContent)
                <p class="mt-2 text-sm text-slate-700">{{ $ctaContent }}</p>
            @endif
            <x-public.lead-action-bar class="mt-4" />
        </section>
    @endif
</div>
</x-public.section>
