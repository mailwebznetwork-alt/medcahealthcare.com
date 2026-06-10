@extends('layouts.app')

@section('title', __('Service categories').' — '.config('medca.brand_name'))

@php
    use App\Services\Public\PublicDisplayNameResolver;

    $displayNames = app(PublicDisplayNameResolver::class);
@endphp

@section('content')
    <x-public.location-page-hero
        :eyebrow="__('Browse by category')"
        :headline="__('Healthcare services')"
        :subline="__('Organize discovery by care type. Each service keeps its own SEO and detail page.')"
        :show-pincode="false"
        :show-actions="true"
        :show-body="false"
        tone="brand"
    />

    <x-public.section class="bg-white">
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($categories as $category)
                <a
                    href="{{ route('public.service-categories.show', $category->code) }}"
                    class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-medca-primary/40 hover:shadow-md"
                >
                    <h2 class="text-lg font-semibold text-slate-900 group-hover:text-medca-primary">{{ $displayNames->categoryHeadline($category) }}</h2>
                    @if ($category->description)
                        <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $category->description }}</p>
                    @endif
                    <p class="mt-4 text-xs text-slate-500">
                        {{ trans_choice(':count service|:count services', $category->services_count, ['count' => $category->services_count]) }}
                    </p>
                    @if ($category->children->isNotEmpty())
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($category->children as $child)
                                <li>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600">{{ $displayNames->categoryHeadline($child) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </a>
            @empty
                <p class="text-slate-600">{{ __('Categories will appear here once published in Operations.') }}</p>
            @endforelse
        </div>
    </x-public.section>
@endsection
