@php
    /** @var \App\Models\Service $service */
    /** @var \App\Models\Page|null $linkedDetailPage */
    $linkedDetailPage = $linkedDetailPage ?? null;
@endphp

<section class="mom-card mb-8 border border-[rgba(197,160,89,0.22)] bg-[rgba(197,160,89,0.04)] p-6">
    <h3 class="mom-section-title mb-2">{{ __('Public page — blocks, SEO & schema') }}</h3>
    <p class="mom-body-text mb-4 max-w-3xl">
        {{ __('Content layout, meta tags, FAQs, JSON-LD, and OG image live on the linked Site Architect page. GEO pincodes stay on the service form below.') }}
    </p>
    <div class="flex flex-wrap gap-3">
        @if ($linkedDetailPage)
            <a href="{{ route('operations.services.detail-page.edit', $service) }}" class="mom-cta-primary">{{ __('Edit blocks & SEO') }}</a>
            <a href="{{ route('site-architect.pages.index', ['edit' => $linkedDetailPage->id]) }}" class="mom-cta-ghost">{{ __('Open in Site Architect') }}</a>
            <span class="mom-subtext self-center">{{ $linkedDetailPage->title }} · {{ $linkedDetailPage->slug }}</span>
        @else
            <a href="{{ route('operations.services.detail-page.create', $service) }}" class="mom-cta-primary">{{ __('Create detail page & open editor') }}</a>
            <p class="mom-subtext w-full">{{ __('Creates page slug :slug and links it to this service.', ['slug' => $suggestedDetailPageSlug ?? 'service-{code}']) }}</p>
        @endif
        <a href="{{ route('site-architect.block-factory.index') }}" class="mom-cta-ghost">{{ __('Blocks Factory') }}</a>
        <a href="{{ route('site-architect.media.index') }}" class="mom-cta-ghost">{{ __('Media library') }}</a>
    </div>
</section>
