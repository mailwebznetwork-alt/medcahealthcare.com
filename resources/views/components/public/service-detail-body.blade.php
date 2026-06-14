@props([
    'service',
    'showReviewForm' => true,
])

@php
    use App\Models\Service;
    use App\Services\Public\PublicDisplayNameResolver;
    use App\Services\Public\CatalogLineIconResolver;
    use App\Support\FaqPairNormalizer;
    use App\Support\ProductCategoryContext;

    if (! $service instanceof Service) {
        return;
    }

    $service->loadMissing(['seo', 'faqs', 'pincodes', 'categories', 'subServices' => fn ($q) => $q->publicListing()]);

    $displayNames = app(PublicDisplayNameResolver::class);
    $iconResolver = app(CatalogLineIconResolver::class);
    $isProductCategory = ProductCategoryContext::isService($service);

    $normalizeList = static function (mixed $value): array {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn ($item) => trim((string) $item), $value),
            static fn (string $item) => $item !== ''
        ));
    };

    $keyBenefits = $iconResolver->keyBenefitsFor($service);
    $eligibility = $normalizeList($service->eligibility);
    $processSteps = $normalizeList($service->process_steps);
    $procedures = $normalizeList($service->procedures);
    $specialized = $normalizeList($service->specialized_care);
    $shifts = $normalizeList($service->shifts);
    $faqs = FaqPairNormalizer::expandMany($service->faqs);
    $subServices = $service->subServices ?? collect();

    $approvedReviews = $service->approvedReviews()->latest()->limit(6)->get();

    $featuredSrc = null;
    if (filled($service->featured_image)) {
        $featuredSrc = \Illuminate\Support\Str::startsWith($service->featured_image, ['http://', 'https://'])
            ? $service->featured_image
            : asset('storage/'.$service->featured_image);
    }

    $galleryImages = collect(is_array($service->gallery) ? $service->gallery : [])
        ->map(static function (mixed $item): ?string {
            if (! is_string($item) || trim($item) === '') {
                return null;
            }

            return \Illuminate\Support\Str::startsWith($item, ['http://', 'https://'])
                ? $item
                : asset('storage/'.$item);
        })
        ->filter()
        ->values();
@endphp

<article {{ $attributes->class(['medca-service-detail']) }} data-service-detail-body="{{ $service->service_code }}">
    <x-public.catalog-trust-panel :entity="$service" class="mb-6" />

    @if ($featuredSrc !== null)
        <figure class="medca-svc-detail-featured">
            <img
                src="{{ $featuredSrc }}"
                alt="{{ $service->image_alt ?? $displayNames->serviceHeadline($service) }}"
                loading="lazy"
                decoding="async"
            />
        </figure>
    @endif

    @if ($galleryImages->isNotEmpty())
        <section class="medca-svc-detail-section">
            <h2 class="medca-svc-detail-heading">{{ __('Gallery') }}</h2>
            <ul class="medca-svc-detail-gallery">
                @foreach ($galleryImages as $image)
                    <li>
                        <img src="{{ $image }}" alt="{{ $service->image_alt ?? $displayNames->serviceHeadline($service) }}" loading="lazy" decoding="async" />
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if (filled($service->description))
        <section class="medca-svc-detail-section">
            <h2 class="medca-svc-detail-heading">{{ __('About this :type', ['type' => $isProductCategory ? __('product') : __('service')]) }}</h2>
            <div class="medca-service-prose prose prose-slate max-w-none prose-headings:text-slate-900 prose-p:text-slate-700">
                {!! $service->description !!}
            </div>
        </section>
    @endif

    <x-public.catalog-why-medca :text="$service->why_medca" />

    @if ($keyBenefits !== [])
        <section class="medca-svc-detail-section">
            <h2 class="medca-svc-detail-heading">{{ __('Key benefits') }}</h2>
            <ul class="medca-svc-detail-benefits">
                @foreach ($keyBenefits as $benefit)
                    <li>
                        <span class="medca-svc-detail-benefit-icon" aria-hidden="true">
                            <x-public.line-icon :name="$benefit['icon']" size="sm" />
                        </span>
                        <span>{{ $benefit['label'] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($processSteps !== [])
        <section class="medca-svc-detail-section">
            <h2 class="medca-svc-detail-heading">{{ __('How it works') }}</h2>
            <ol class="medca-svc-detail-steps">
                @foreach ($processSteps as $index => $step)
                    <li>
                        <span class="medca-svc-detail-step-num">{{ $index + 1 }}</span>
                        <span>{{ $step }}</span>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    @if ($eligibility !== [])
        <section class="medca-svc-detail-section">
            <h2 class="medca-svc-detail-heading">{{ __('Who is this for?') }}</h2>
            <ul class="medca-svc-detail-list">
                @foreach ($eligibility as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($procedures !== [] || $specialized !== [] || $shifts !== [])
        <section class="medca-svc-detail-section">
            <h2 class="medca-svc-detail-heading">{{ __('What is included') }}</h2>
            <div class="medca-svc-detail-grid">
                @if ($procedures !== [])
                    <div class="medca-svc-detail-card">
                        <h3>{{ __('Procedures included') }}</h3>
                        <ul>
                            @foreach ($procedures as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($specialized !== [])
                    <div class="medca-svc-detail-card">
                        <h3>{{ __('Specialized care') }}</h3>
                        <ul>
                            @foreach ($specialized as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($shifts !== [])
                    <div class="medca-svc-detail-card">
                        <h3>{{ $isProductCategory ? __('Availability') : __('Service timing') }}</h3>
                        <ul>
                            @foreach ($shifts as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if ($subServices->isNotEmpty())
        <section class="medca-svc-detail-section">
            <h2 class="medca-svc-detail-heading">{{ __('Sub-services') }}</h2>
            <ul class="medca-svc-detail-subs">
                @foreach ($subServices as $sub)
                    <li>
                        <a href="{{ route('public.services.sub', [$service->service_code, $sub->sub_service_code]) }}" class="medca-svc-detail-sub-card group">
                            <span class="medca-svc-detail-sub-icon" aria-hidden="true">
                                <x-public.line-icon :model="$sub" size="sm" />
                            </span>
                            <span class="medca-svc-detail-sub-title group-hover:text-medca-primary">{{ $displayNames->subServiceHeadline($sub) }}</span>
                            <x-public.catalog-card-summary :model="$sub" class="medca-svc-detail-sub-summary" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($faqs !== [])
        <section class="medca-svc-detail-section">
            <h2 class="medca-svc-detail-heading">{{ __('Frequently asked questions') }}</h2>
            <dl class="medca-svc-detail-faqs">
                @foreach ($faqs as $faq)
                    <div class="medca-svc-detail-faq">
                        <dt>{{ $faq['question'] }}</dt>
                        <dd>{{ $faq['answer'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    @if ($approvedReviews->isNotEmpty())
        <section class="medca-svc-detail-section">
            <h2 class="medca-svc-detail-heading">{{ __('Patient reviews') }}</h2>
            <ul class="medca-svc-detail-reviews">
                @foreach ($approvedReviews as $review)
                    <li class="medca-svc-detail-review">
                        <p class="medca-svc-detail-review-stars" aria-hidden="true">{{ str_repeat('★', min(5, (int) $review->rating)) }}</p>
                        @if (filled($review->comment))
                            <p class="medca-svc-detail-review-text">{{ $review->comment }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($showReviewForm)
        <section class="medca-svc-detail-section medca-svc-detail-review-form">
            @livewire('reviews.review-form', ['serviceId' => $service->id], key('review-form-'.$service->id))
        </section>
    @endif

    <div class="medca-svc-detail-cta">
        <x-public.lead-action-bar />
    </div>
</article>
