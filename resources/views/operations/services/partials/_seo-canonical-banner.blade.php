@php
    use App\Services\Operations\ServiceSeoOwnership;
    $pageSeoCanonical = ServiceSeoOwnership::pageSeoOverridesService($linkedDetailPage ?? null);
@endphp
@if ($pageSeoCanonical)
    <div class="mb-6 rounded-mom-chrome border border-[rgba(197,160,89,0.35)] bg-[rgba(197,160,89,0.08)] px-4 py-3 text-sm text-[var(--text-secondary)]" role="status">
        <p class="font-semibold text-mom-gold">{{ __('Page SEO is canonical') }}</p>
        <p class="mt-1">{{ __('The linked Site Architect page has meta/H1 filled. Public rendering uses page fields; service SEO below is fallback only.') }}</p>
        @if (isset($linkedDetailPage) && $linkedDetailPage)
            <p class="mt-2">
                <a href="{{ route('site-architect.pages.index', ['edit' => $linkedDetailPage->id]) }}" class="text-mom-gold underline">{{ __('Edit page SEO') }}</a>
            </p>
        @endif
    </div>
@elseif (isset($linkedDetailPage) && $linkedDetailPage)
    <p class="mom-subtext mb-4 max-w-3xl">{{ __('Linked detail page exists. Fill page meta in Site Architect for canonical SEO; service SEO is used until then and for /services/CODE fallback.') }}</p>
@endif
