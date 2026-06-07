@php
    $sub = $subService ?? null;
    $parent = $sub?->service;
    $headline = $sub?->seo?->h1 ?: $sub?->title;
    $summary = $sub?->short_summary ?: $sub?->description ?: $sub?->seo?->meta_description;
@endphp
@if ($sub)
<x-public.section>
    <header class="space-y-4 border-b border-slate-200 pb-6">
        @if ($parent)
            <p class="text-sm text-slate-500"><a href="{{ $parent->publicUrl() }}" class="hover:underline">{{ $parent->title }}</a></p>
        @endif
        <h1 class="text-3xl font-semibold text-slate-900">{{ $headline }}</h1>
        @if (filled($summary))
            <p class="max-w-3xl text-slate-600">{{ $summary }}</p>
        @endif
        <x-public.lead-action-bar />
    </header>
</x-public.section>
@endif
