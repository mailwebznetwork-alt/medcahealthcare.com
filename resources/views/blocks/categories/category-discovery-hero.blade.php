@php
    use App\Services\Public\PublicDisplayNameResolver;

    $category = $category ?? ($serviceCategory ?? null);
    $displayNames = app(PublicDisplayNameResolver::class);
    $headline = $category ? $displayNames->categoryHeadline($category) : '';
    $summary = $category ? ($displayNames->categoryMetaDescription($category) ?: $category->description) : '';
@endphp
@if ($category)
<x-public.section>
    <header class="space-y-4 border-b border-slate-200 pb-6">
        <p class="medca-eyebrow text-slate-500">{{ __('Category') }}</p>
        <h1 class="text-3xl font-semibold text-slate-900 md:text-4xl">{{ $headline }}</h1>
        @if (filled($summary))
            <p class="medca-subheadline max-w-3xl text-slate-600">{{ $summary }}</p>
        @endif
    </header>
</x-public.section>
@endif
