@props([
    'subService',
])

@php
    use App\Models\SubService;
    use App\Services\Public\CatalogLineIconResolver;
    use App\Services\Public\PublicDisplayNameResolver;
    use App\Support\FaqPairNormalizer;

    if (! $subService instanceof SubService) {
        return;
    }

    $subService->loadMissing(['seo', 'faqs', 'service']);
    $iconResolver = app(CatalogLineIconResolver::class);
    $displayNames = app(PublicDisplayNameResolver::class);

    $normalizeList = static function (mixed $value): array {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn ($item) => trim((string) $item), $value),
            static fn (string $item) => $item !== ''
        ));
    };

    $keyBenefits = $iconResolver->keyBenefitsFor($subService);
    $processSteps = $normalizeList($subService->process_steps);
    $faqs = FaqPairNormalizer::expandMany($subService->faqs);
@endphp

<article {{ $attributes->class(['medca-service-detail medca-sub-service-detail']) }} data-sub-service-detail-body="{{ $subService->sub_service_code }}">
    <x-public.catalog-trust-panel :entity="$subService" class="mb-6" />

    @if (filled($subService->description))
        <section class="medca-svc-detail-section">
            <h2 class="medca-svc-detail-heading">{{ __('About this service') }}</h2>
            <div class="medca-service-prose prose prose-slate max-w-none prose-p:text-slate-700">
                {!! $subService->description !!}
            </div>
        </section>
    @elseif (filled($subService->short_summary))
        <section class="medca-svc-detail-section">
            <p class="text-slate-600">{{ $subService->short_summary }}</p>
        </section>
    @endif

    <x-public.catalog-why-medca :text="$subService->why_medca" />

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

    @if ($subService->service)
        <div class="medca-svc-detail-cta">
            <a href="{{ $subService->service->publicUrl() }}" class="text-sm font-semibold text-medca-primary underline underline-offset-2">
                {{ __('View parent service: :title', ['title' => $displayNames->serviceHeadline($subService->service)]) }} →
            </a>
        </div>
    @endif
</article>
