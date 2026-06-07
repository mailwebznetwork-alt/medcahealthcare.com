<div class="space-y-8">
    <x-public.location-heading-with-pincode
        :eyebrow="$eyebrow"
        :headline="$headline"
        :subline="$subline"
        heading-tag="h2"
        :pincode-button="$pincodeButton"
        tone="light"
    />

    @if (! $locationRequired)
        <x-public.lead-action-bar />
    @endif

    @if ($locationRequired)
        <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {{ __('Set your Bangalore pincode to see hyper-local services available in your area.') }}
        </p>
    @elseif ($services->isEmpty())
        <p class="text-sm text-slate-600">{{ $emptyServicesMessage }}</p>
    @elseif ($detailedServices ?? false)
        <x-public.location-services-detail-list
            :services="$services"
            :pin-code-record="$pinCodeRecord"
            :section-title="__('Healthcare services in your area')"
            :empty-message="$emptyServicesMessage"
        />
    @else
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($services as $service)
                @php
                    $categoryName = $service->categories->first()?->name;
                @endphp
                <li>
                    <a href="{{ route('public.services.show', $service->service_code) }}" class="group flex h-full flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-medca-primary/30 hover:shadow-md">
                        @if ($categoryName)
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-medca-primary">{{ $categoryName }}</span>
                        @endif
                        <h3 class="mt-1 text-base font-semibold text-slate-900 group-hover:text-medca-primary">{{ $service->title }}</h3>
                        @if (filled($service->short_summary))
                            <p class="medca-card-body mt-2 flex-1">{{ \Illuminate\Support\Str::limit(strip_tags($service->short_summary), 120) }}</p>
                        @endif
                        <span class="mt-4 text-sm font-semibold text-medca-primary">{{ __('View details') }} →</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <x-public.lead-action-bar class="pt-2" />
    @endif

    @if ($pinCodeRecord && filled($pinCodeRecord->coverage_text))
        <section class="rounded-xl border border-slate-200 bg-slate-50 p-6">
            <h3 class="text-lg font-semibold text-slate-900">{{ __('About :area healthcare coverage', ['area' => $pinCodeRecord->area_name]) }}</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $pinCodeRecord->coverage_text }}</p>
            @php $pinCodeRecord->loadMissing(['landmarks', 'hospitals', 'nearbyAreas']); @endphp
            @if ($pinCodeRecord->landmarks->isNotEmpty() || $pinCodeRecord->hospitals->isNotEmpty() || $pinCodeRecord->nearbyAreas->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-2 text-xs text-slate-600">
                    @foreach ($pinCodeRecord->landmarks->take(4) as $landmark)
                        <span class="rounded-full bg-white px-3 py-1 ring-1 ring-slate-200">{{ $landmark->name }}</span>
                    @endforeach
                    @foreach ($pinCodeRecord->hospitals->take(3) as $hospital)
                        <span class="rounded-full bg-white px-3 py-1 ring-1 ring-slate-200">{{ $hospital->name }}</span>
                    @endforeach
                    @foreach ($pinCodeRecord->nearbyAreas->take(3) as $nearby)
                        <span class="rounded-full bg-white px-3 py-1 ring-1 ring-slate-200">{{ $nearby->area_name }}</span>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</div>
