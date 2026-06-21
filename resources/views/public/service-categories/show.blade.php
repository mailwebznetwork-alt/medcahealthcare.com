@extends('layouts.app')

@php
    use App\Services\Public\PublicDisplayNameResolver;
    use App\Support\ProductCategoryContext;

    $displayNames = app(PublicDisplayNameResolver::class);
    $isProductCategory = ProductCategoryContext::isCategory($category);
@endphp

@section('title', $displayNames->categoryMetaTitle($category).' — '.config('medca.brand_name'))

@section('content')
    <x-public.location-page-hero
        :eyebrow="__('Category')"
        :headline="$displayNames->categoryHeadline($category)"
        :subline="$category->description"
        :show-country="false"
        :show-actions="true"
        :show-body="false"
        tone="brand"
    />

    <x-public.section class="bg-white">
        @php
            $categoryBreadcrumbItems = [
                ['label' => __('Categories'), 'url' => route('public.service-categories.index')],
            ];
            if ($category->parent) {
                $categoryBreadcrumbItems[] = [
                    'label' => $displayNames->categoryHeadline($category->parent),
                    'url' => route('public.service-categories.show', $category->parent->code),
                ];
            }
            $categoryBreadcrumbItems[] = ['label' => $displayNames->categoryHeadline($category), 'url' => '#'];
        @endphp
        <x-public.breadcrumbs :items="$categoryBreadcrumbItems" />

        @if ($siblingCategories->isNotEmpty())
            <div class="mt-6 flex flex-wrap gap-2" aria-label="{{ __('Service Category') }}">
                @foreach ($siblingCategories as $sibling)
                    <a href="{{ route('public.service-categories.show', $sibling->code) }}" class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm text-slate-700 hover:border-medca-primary/40 hover:text-medca-primary">
                        {{ $displayNames->categoryHeadline($sibling) }}
                    </a>
                @endforeach
            </div>
        @endif

        @if ($locationRequired)
            <p class="mt-8 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ __('Set your pincode to see services available in your area.') }}
            </p>
            <button
                type="button"
                onclick="window.dispatchEvent(new CustomEvent('open-pincode-modal', { detail: { contextPath: window.location.pathname } }))"
                class="mt-4 inline-flex items-center justify-center rounded-xl bg-medca-primary px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-medca-primary-hover"
            >{{ __('Select pincode') }}</button>
        @else
            @if ($pincode)
                <p class="mt-6 text-sm text-slate-600">{{ $isProductCategory ? __('Showing products for pincode :pin', ['pin' => $pincode]) : __('Showing services for pincode :pin', ['pin' => $pincode]) }}</p>
            @endif

            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($services as $service)
                    <x-public.service-card
                        :service="$service"
                        heading-tag="h2"
                        show-price
                        show-categories
                        :product-category="$isProductCategory"
                    />
                @empty
                    <p class="text-slate-600 sm:col-span-2 lg:col-span-3">{{ $isProductCategory ? __('No published products in this category for your area yet.') : __('No published services in this category for your area yet.') }}</p>
                @endforelse
            </div>

            @if ($services->hasPages())
                <div class="mt-10">
                    {{ $services->links() }}
                </div>
            @endif
        @endif
    </x-public.section>
@endsection
