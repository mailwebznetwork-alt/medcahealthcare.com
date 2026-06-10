@props([
    'service',
    'showReviewForm' => true,
])

@php
    use App\Models\Service;
    use App\Services\Public\PublicDisplayNameResolver;
    use App\Support\FaqPairNormalizer;
    use App\Support\ProductCategoryContext;

    if (! $service instanceof Service) {
        return;
    }

    $service->loadMissing(['seo', 'faqs', 'pincodes', 'categories', 'subServices' => fn ($q) => $q->publicListing()]);

    $displayNames = app(PublicDisplayNameResolver::class);
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

    $keyBenefits = $normalizeList($service->key_benefits);
    $eligibility = $normalizeList($service->eligibility);
    $processSteps = $normalizeList($service->process_steps);
    $procedures = $normalizeList($service->procedures);
    $specialized = $normalizeList($service->specialized_care);
    $shifts = $normalizeList($service->shifts);
    $trustSignals = $normalizeList($service->trust_signals);
    $faqs = FaqPairNormalizer::expandMany($service->faqs);
    $subServices = $service->subServices ?? collect();

    $averageRating = $service->averageApprovedRating();
    $reviewsCount = $service->approvedReviewsCount();
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
    @if ($averageRating !== null && $reviewsCount > 0)
        <div class="medca-svc-detail-rating">
            <span class="medca-svc-detail-rating-stars" aria-hidden="true">{{ str_repeat('★', min(5, (int) round($averageRating))) }}</span>
            <span class="medca-svc-detail-rating-text">
                {{ __(':rating / 5 · :count reviews', ['rating' => number_format((float) $averageRating, 1), 'count' => $reviewsCount]) }}
            </span>
        </div>
    @endif

    @if ($trustSignals !== [])
        <ul class="medca-svc-detail-trust" aria-label="{{ __('Trust highlights') }}">
            @foreach ($trustSignals as $signal)
                <li>{{ $signal }}</li>
            @endforeach
        </ul>
    @endif

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

    @if ($keyBenefits !== [])
        <section class="medca-svc-detail-section">
            <h2 class="medca-svc-detail-heading">{{ __('Key benefits') }}</h2>
            <ul class="medca-svc-detail-benefits">
                @foreach ($keyBenefits as $benefit)
                    <li>{{ $benefit }}</li>
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
                        <a href="{{ route('public.services.sub', [$service->service_code, $sub->sub_service_code]) }}" class="medca-svc-detail-sub-card">
                            <span class="medca-svc-detail-sub-title">{{ $displayNames->subServiceHeadline($sub) }}</span>
                            @if (filled($sub->short_summary))
                                <span class="medca-svc-detail-sub-summary">{{ \Illuminate\Support\Str::limit(strip_tags($sub->short_summary), 120) }}</span>
                            @endif
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
