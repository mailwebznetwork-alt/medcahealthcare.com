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

    $provisioner = app(\App\Services\Operations\ServiceLocationPageProvisioner::class);
    $templates = app(\App\Services\Operations\ServiceLocationTemplateResolver::class);
    $area = $pin->area_name ?: $pin->locality ?: $pin->city ?: $pin->pincode;
    $title = $provisioner->locationTitle($svc, $pin);
    $intro = $provisioner->localIntro($svc, $pin);
    $ctaHeading = $templates->ctaHeading($svc, $pin);
    $ctaContent = $templates->ctaContent($svc, $pin);

    $coverageAreas = PinCode::query()
        ->where('is_active', true)
        ->whereKeyNot($pin->id)
        ->orderBy('state')
        ->orderBy('city')
        ->orderBy('pincode')
        ->get();
@endphp

<x-public.location-page-hero
    :eyebrow="__('Service Areas')"
    :headline="$title"
    :subline="__('Medical lab services available for :area.', ['area' => $area, 'pin' => $pin->pincode])"
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

        <x-public.locations-coverage-grid
            :areas="$coverageAreas"
            :exclude-country-ids="[$pin->id]"
            :service="$svc"
            :title="__('Areas & Pincodes We Serve')"
            :initial="8"
        />

        <x-public.location-about-coverage :pin="$pin" :intro="$intro" />

        <x-public.location-local-faq :pin="$pin" />

        <x-public.location-area-cta :heading="$ctaHeading" :content="$ctaContent" />
    </div>
</x-public.section>
