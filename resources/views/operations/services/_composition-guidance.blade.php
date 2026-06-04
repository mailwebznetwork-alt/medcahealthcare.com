@php
    $hasLinkedPage = isset($linkedDetailPage) && $linkedDetailPage !== null;
    $patternPage = $patternDetailPage ?? null;
@endphp

<section class="mom-card mb-6 border border-[rgba(197,160,89,0.22)] bg-[rgba(197,160,89,0.05)] p-4" role="note">
    <h3 class="text-sm font-semibold text-mom-gold">{{ __('Composition & ownership') }}</h3>
    <p class="mom-subtext mt-2 max-w-3xl">
        {{ __('This form owns service facts (title, summary, media, GEO, fallback SEO/FAQ). What visitors see on /services/:code is built from the linked Site Architect page and blocks — not from this form alone.', ['code' => $service->service_code]) }}
    </p>
    <ul class="mom-subtext mt-3 list-inside list-disc space-y-1 text-sm">
        <li>{{ __('Layout & block order') }} → <strong>{{ __('Site Architect → Pages') }}</strong></li>
        <li>{{ __('Hero / CTA / contact copy in blocks') }} → <strong>{{ __('Blocks Factory') }}</strong> + <strong>{{ __('Blocks Studio') }}</strong> (settings)</li>
        <li>{{ __('Live meta when page is filled') }} → <strong>{{ __('Page SEO tab') }}</strong></li>
        <li>{{ __('Header / phone / brand') }} → <strong>{{ __('Settings → Global content / Theme') }}</strong></li>
    </ul>
    @if ($hasLinkedPage)
        <p class="mom-body-text mt-3 text-sm">
            {{ __('Linked page: :title (:slug)', ['title' => $linkedDetailPage->title, 'slug' => $linkedDetailPage->slug]) }}
            @can('view', $linkedDetailPage)
                · <a href="{{ route('site-architect.pages.preview', $linkedDetailPage) }}" class="text-mom-gold underline" target="_blank" rel="noopener">{{ __('Preview (production path)') }}</a>
            @endcan
        </p>
    @elseif ($patternPage !== null)
        <p class="mom-body-text mt-3 text-sm">
            {{ __('Auto page slug :slug is active when no page is selected above.', ['slug' => $patternPage->slug]) }}
        </p>
    @else
        <p class="mom-body-text mt-3 text-sm text-[var(--text-muted)]">
            {{ __('No detail page yet — public URL uses the fallback service template until you create/link a page.') }}
        </p>
    @endif
</section>
