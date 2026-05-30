@php
    $services = $services ?? collect();
    $pincode = $pincode ?? null;
    $pinCodeRecord = $pinCodeRecord ?? null;
    $locationRequired = (bool) ($locationRequired ?? false);
@endphp

<section class="border-t border-slate-200 bg-white py-10" data-section="near-you">
    <div class="mx-auto max-w-6xl px-4 md:px-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-[#0046ad]">{{ __('Near You') }}</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">
                    @if ($pincode)
                        {{ __('Services in :area', ['area' => $pinCodeRecord?->area_name ?: $pincode]) }}
                    @else
                        {{ __('Services near your pincode') }}
                    @endif
                </h2>
                @if ($pincode)
                    <p class="mt-1 text-sm text-slate-600">{{ __('Showing coverage for pincode :pin', ['pin' => $pincode]) }}</p>
                @endif
            </div>
            <button
                type="button"
                onclick="window.dispatchEvent(new CustomEvent('open-pincode-modal'))"
                class="text-sm font-semibold text-[#0046ad] underline underline-offset-2"
            >{{ $pincode ? __('Change pincode') : __('Set pincode') }}</button>
        </div>

        @if ($locationRequired)
            <p class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ __('Set your Bangalore pincode to see hyper-local services available in your area.') }}
            </p>
        @elseif ($services->isEmpty())
            <p class="mt-6 text-sm text-slate-600">{{ __('No published services are mapped to this pincode yet.') }}</p>
        @else
            <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    <li>
                        <a href="{{ route('public.services.show', $service->service_code) }}" class="block h-full rounded-xl border border-slate-200 bg-slate-50 p-5 shadow-sm transition hover:border-[#0046ad]/30 hover:shadow-md">
                            <h3 class="text-base font-semibold text-slate-900">{{ $service->title }}</h3>
                            @if (filled($service->short_summary))
                                <p class="mt-2 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit(strip_tags($service->short_summary), 120) }}</p>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
