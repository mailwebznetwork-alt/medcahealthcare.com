@props([
    'category',
])

@php
    use App\Models\ServiceCategory;
    use App\Services\Public\CatalogLineIconResolver;
    use App\Support\FaqPairNormalizer;

    $category = $category ?? null;
    if (! $category instanceof ServiceCategory) {
        return;
    }

    $category->loadMissing(['faqs', 'seo']);
    $iconResolver = app(CatalogLineIconResolver::class);
    $keyBenefits = $iconResolver->keyBenefitsFor($category);
    $faqs = FaqPairNormalizer::expandMany($category->faqs);
@endphp

<article {{ $attributes->class(['medca-category-detail mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-8']) }} data-category-detail-body="{{ $category->code }}">
    <x-public.catalog-trust-panel :entity="$category" class="mb-6" />

    @if (filled($category->description))
        <section class="medca-svc-detail-section">
            <h2 class="medca-svc-detail-heading">{{ __('About this category') }}</h2>
            <div class="medca-service-prose prose prose-slate max-w-none prose-p:text-slate-700">
                {!! $category->description !!}
            </div>
        </section>
    @endif

    <x-public.catalog-why-medca :text="$category->why_medca" />

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
</article>
