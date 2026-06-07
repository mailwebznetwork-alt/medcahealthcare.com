@props([
    'service',
    'pinCode' => null,
    'compactFaqs' => true,
])

@php
    use App\Models\PinCode;
    use App\Models\Service;
    use App\Models\ServiceLocationPage;
    use App\Services\Operations\ServicePublicUrlBuilder;

    if (! $service instanceof Service) {
        return;
    }

    $service->loadMissing(['seo', 'categories', 'faqs']);

    $categoryName = $service->categories->first()?->name;
    $featuredSrc = null;

    if (filled($service->featured_image)) {
        $featuredSrc = \Illuminate\Support\Str::startsWith($service->featured_image, ['http://', 'https://'])
            ? $service->featured_image
            : asset('storage/'.$service->featured_image);
    }

    $trustSignals = is_array($service->trust_signals) ? array_slice($service->trust_signals, 0, 4) : [];
    $procedures = is_array($service->procedures) ? array_values(array_filter($service->procedures)) : [];
    $specialized = is_array($service->specialized_care) ? array_values(array_filter($service->specialized_care)) : [];
    $shifts = is_array($service->shifts) ? array_values(array_filter($service->shifts)) : [];
    $faqs = $service->faqs->filter(
        fn ($faq) => filled(trim((string) $faq->question)) && filled(trim((string) $faq->answer))
    );

    $locationUrl = null;
    if ($pinCode instanceof PinCode) {
        $mapping = ServiceLocationPage::query()
            ->where('service_id', $service->id)
            ->where('pincode_id', $pinCode->id)
            ->first();

        $locationUrl = app(ServicePublicUrlBuilder::class)->locationUrlForPin($service, $pinCode, $mapping);
    }

    $serviceUrl = route('public.services.show', $service->service_code);
@endphp

<article {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-6 shadow-sm md:p-8']) }} data-location-service="{{ $service->service_code }}">
    <div class="space-y-5">
        @if ($categoryName)
            <p class="text-[10px] font-semibold uppercase tracking-wide text-medca-primary">{{ $categoryName }}</p>
        @endif

        <div class="flex flex-wrap items-start justify-between gap-4">
            <h3 class="text-xl font-semibold text-slate-900 md:text-2xl">{{ $service->seo?->h1 ?: $service->title }}</h3>
            @if ($service->hasPriceRange())
                <span class="rounded-full bg-slate-100 px-4 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-slate-200">{{ $service->price_range }}</span>
            @endif
        </div>

        @if (filled($service->short_summary))
            <p class="medca-text-body-lg max-w-3xl text-slate-600">{{ $service->short_summary }}</p>
        @endif

        @if ($trustSignals !== [])
            <ul class="flex flex-wrap gap-2">
                @foreach ($trustSignals as $signal)
                    <li class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-200">{{ $signal }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    @if ($featuredSrc)
        <figure class="mt-6 overflow-hidden rounded-xl border border-slate-200">
            <img
                src="{{ $featuredSrc }}"
                alt="{{ $service->image_alt ?? $service->title }}"
                class="max-h-72 w-full object-cover"
                loading="lazy"
                decoding="async"
            />
        </figure>
    @endif

    @if (filled($service->description))
        <div class="medca-service-prose prose prose-slate mt-6 max-w-none prose-headings:text-slate-900 prose-p:text-slate-700">
            {!! $service->description !!}
        </div>
    @endif

    @if ($procedures !== [] || $specialized !== [] || $shifts !== [])
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            @if ($procedures !== [])
                <section class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <h4 class="text-sm font-semibold text-slate-900">{{ __('Procedures included') }}</h4>
                    <ul class="mt-2 space-y-1 text-sm text-slate-600">
                        @foreach (array_slice($procedures, 0, 6) as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif
            @if ($specialized !== [])
                <section class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <h4 class="text-sm font-semibold text-slate-900">{{ __('Specialized care') }}</h4>
                    <ul class="mt-2 space-y-1 text-sm text-slate-600">
                        @foreach (array_slice($specialized, 0, 6) as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif
            @if ($shifts !== [])
                <section class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <h4 class="text-sm font-semibold text-slate-900">{{ __('Service timing') }}</h4>
                    <ul class="mt-2 space-y-1 text-sm text-slate-600">
                        @foreach (array_slice($shifts, 0, 6) as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    @endif

    @if ($faqs->isNotEmpty())
        <section class="mt-6 space-y-3">
            <h4 class="text-base font-semibold text-slate-900">{{ __('Frequently asked questions') }}</h4>
            <dl class="space-y-3">
                @foreach ($faqs->take($compactFaqs ? 3 : 10) as $faq)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <dt class="font-semibold text-slate-900">{{ $faq->question }}</dt>
                        <dd class="mt-2 text-sm leading-relaxed text-slate-600">{{ $faq->answer }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    <div class="mt-6 flex flex-wrap items-center gap-4">
        @if ($locationUrl)
            <a href="{{ $locationUrl }}" class="text-sm font-semibold text-medca-primary underline underline-offset-2">
                {{ __('View in your area') }} →
            </a>
        @endif
        <a href="{{ $serviceUrl }}" class="text-sm font-semibold text-slate-700 underline underline-offset-2 hover:text-medca-primary">
            {{ __('Full service details') }} →
        </a>
    </div>
</article>
