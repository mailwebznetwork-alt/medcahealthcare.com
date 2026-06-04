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

    if ($pincode) {
        $area = $pinCodeRecord?->area_name ?: $pincode;
        $template = $useBlockCopy
            ? BlockContent::get($settings, $contentSlug, 'headline_with_area')
            : __('Services in :area');
        $headline = str_replace(':area', $area, $template);
    } else {
        $headline = $copy('headline_no_pincode', 'Services near your pincode');
    }

    $pincodeLine = $pincode
        ? str_replace(
            ':pin',
            (string) $pincode,
            $useBlockCopy
                ? BlockContent::get($settings, $contentSlug, 'pincode_line')
                : __('Showing coverage for pincode :pin')
        )
        : null;

    $pincodeButton = $pincode
        ? $copy('change_pincode_label', 'Change pincode')
        : $copy('set_pincode_label', 'Set pincode');

    $locationRequiredMessage = $copy(
        'location_required_message',
        'Set your Bangalore pincode to see hyper-local services available in your area.'
    );

    $emptyServicesMessage = $copy(
        'empty_services_message',
        'No published services are mapped to this pincode yet.'
    );
@endphp

@if ($isAdmin)
    <div class="px-5 py-5 md:px-6 md:py-6" data-section="near-you">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="mom-micro">{{ $eyebrow }}</p>
                <h2 class="mom-section-title mt-2">{{ $headline }}</h2>
                @if ($pincodeLine)
                    <p class="mom-subtext mt-1">{{ $pincodeLine }}</p>
                @endif
            </div>
            <button
                type="button"
                onclick="window.dispatchEvent(new CustomEvent('open-pincode-modal'))"
                class="mom-cta-compact mom-cta-ghost !normal-case !tracking-normal"
            >{{ $pincodeButton }}</button>
        </div>

        @if ($locationRequired)
            <p class="mt-6 rounded-mom-chrome border border-[rgba(226,184,92,0.35)] bg-[rgba(226,184,92,0.08)] px-4 py-3 text-sm text-[var(--warning)]">
                {{ $locationRequiredMessage }}
            </p>
        @elseif ($services->isEmpty())
            <p class="mom-body-text mt-6 text-[var(--text-secondary)]">{{ $emptyServicesMessage }}</p>
        @else
            <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    <li>
                        <a href="{{ route('public.services.show', $service->service_code) }}" target="_blank" rel="noopener" class="mom-card mom-card-interactive block h-full p-5 no-underline">
                            <h3 class="text-base font-semibold text-[var(--text-primary)]">{{ $service->title }}</h3>
                            @if (filled($service->short_summary))
                                <p class="mom-body-text mt-2">{{ \Illuminate\Support\Str::limit(strip_tags($service->short_summary), 120) }}</p>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@else
    <x-public.full-bleed class="border-t border-slate-200 bg-white py-10 md:py-12" data-section="near-you">
        <x-public.content-shell>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-medca-primary">{{ $eyebrow }}</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">{{ $headline }}</h2>
                    @if ($pincodeLine)
                        <p class="mt-1 text-sm text-slate-600">{{ $pincodeLine }}</p>
                    @endif
                </div>
                <button
                    type="button"
                    onclick="window.dispatchEvent(new CustomEvent('open-pincode-modal'))"
                    class="text-sm font-semibold text-medca-primary underline underline-offset-2"
                >{{ $pincodeButton }}</button>
            </div>

            @if ($locationRequired)
                <p class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ $locationRequiredMessage }}
                </p>
            @elseif ($services->isEmpty())
                <p class="mt-6 text-sm text-slate-600">{{ $emptyServicesMessage }}</p>
            @else
                <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services as $service)
                        <li>
                            <a href="{{ route('public.services.show', $service->service_code) }}" class="block h-full rounded-xl border border-slate-200 bg-slate-50 p-5 shadow-sm transition hover:border-medca-primary/30 hover:shadow-md">
                                <h3 class="text-base font-semibold text-slate-900">{{ $service->title }}</h3>
                                @if (filled($service->short_summary))
                                    <p class="medca-card-body mt-2">{{ \Illuminate\Support\Str::limit(strip_tags($service->short_summary), 120) }}</p>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-public.content-shell>
    </x-public.full-bleed>
@endif
