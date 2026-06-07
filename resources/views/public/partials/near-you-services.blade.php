@php
    use App\Support\BlockContent;

    $services = $services ?? collect();
    $pincode = $pincode ?? null;
    $pinCodeRecord = $pinCodeRecord ?? null;
    $locationRequired = (bool) ($locationRequired ?? false);
    $variant = $variant ?? 'public';
    $isAdmin = $variant === 'admin';

    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $contentSlug = is_string($contentSlug ?? null) ? $contentSlug : null;
    $useBlockCopy = $contentSlug !== null && BlockContent::hasSchema($contentSlug);

    $copy = static function (string $key, string $fallback) use ($useBlockCopy, $settings, $contentSlug): string {
        if ($useBlockCopy) {
            return BlockContent::get($settings, $contentSlug, $key);
        }

        return __($fallback);
    };

    $eyebrow = $copy('eyebrow', 'Near You');
    $area = $pinCodeRecord?->area_name ?: ($pincode ?: __('your area'));
    $city = $pinCodeRecord?->city ?: 'Bangalore';

    if ($pincode) {
        $headline = __('Healthcare Services in :area', ['area' => $area]);
        $subline = __('Professional healthcare services available in :area (:pin).', ['area' => $area, 'pin' => $pincode]);
    } else {
        $headline = $copy('headline_no_pincode', 'Healthcare services near your pincode');
        $subline = $copy('location_required_message', 'Set your Bangalore pincode to see hyper-local services available in your area.');
    }

    $pincodeButton = $pincode
        ? $copy('change_pincode_label', 'Change Pincode')
        : $copy('set_pincode_label', 'Set Pincode');

    $emptyServicesMessage = $copy(
        'empty_services_message',
        'No published services are mapped to this pincode yet.'
    );

    $detailedServices = ($contentSlug ?? '') === 'near-you-locations';

@endphp

@if ($isAdmin)
    <div class="px-5 py-5 md:px-6 md:py-6" data-section="near-you">
        @include('public.partials.near-you-hero-inner', compact('eyebrow', 'headline', 'subline', 'pincode', 'pincodeButton', 'locationRequired', 'emptyServicesMessage', 'services', 'pinCodeRecord', 'isAdmin', 'detailedServices'))
    </div>
@else
    <x-public.full-bleed class="border-t border-slate-200 bg-white py-10 md:py-12" data-section="near-you">
        <x-public.content-shell>
            @include('public.partials.near-you-hero-inner', compact('eyebrow', 'headline', 'subline', 'pincode', 'pincodeButton', 'locationRequired', 'emptyServicesMessage', 'services', 'pinCodeRecord', 'isAdmin', 'detailedServices'))
        </x-public.content-shell>
    </x-public.full-bleed>
@endif
