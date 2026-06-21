@php
    use App\Support\BlockContent;

    $categories = $categories ?? collect();
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

    $eyebrow = $copy('eyebrow', 'Service Areas');
    $area = $pinCodeRecord?->area_name ?: ($pincode ?: __('your area'));
    $city = $pinCodeRecord?->city ?: 'India';

    if ($pincode) {
        $headline = __('Healthcare Career Services in :area', ['area' => $area]);
        $subline = __('Professional healthcare career consultancy categories available for :area.', ['area' => $area, 'pin' => $pincode]);
    } else {
        $headline = $copy('headline_no_pincode', 'medical lab services by pincode');
        $subline = $copy('location_required_message', 'Select your pincode to see services available in your area.');
    }

    $pincodeButton = $pincode
        ? $copy('change_pincode_label', 'Change Pincode')
        : $copy('set_pincode_label', 'Select Pincode');

    $emptyCategoriesMessage = $copy(
        'empty_categories_message',
        $copy('empty_services_message', 'No published categories are mapped to this pincode yet.')
    );

@endphp

@if ($isAdmin)
    <div class="px-5 py-5 md:px-6 md:py-6" id="near-you" data-section="near-you">
        @include('public.partials.near-you-hero-inner', compact('eyebrow', 'headline', 'subline', 'pincode', 'pincodeButton', 'locationRequired', 'emptyCategoriesMessage', 'categories', 'pinCodeRecord', 'isAdmin'))
    </div>
@else
    <x-public.full-bleed class="border-t border-slate-200 bg-white py-10 md:py-12" id="near-you" data-section="near-you">
        <x-public.content-shell>
            @include('public.partials.near-you-hero-inner', compact('eyebrow', 'headline', 'subline', 'pincode', 'pincodeButton', 'locationRequired', 'emptyCategoriesMessage', 'categories', 'pinCodeRecord', 'isAdmin'))
        </x-public.content-shell>
    </x-public.full-bleed>
@endif
