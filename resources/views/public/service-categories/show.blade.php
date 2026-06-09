@extends('layouts.app')

@section('title', $category->name.' — '.config('medca.brand_name'))

@section('content')
    <x-public.location-page-hero
        :eyebrow="__('Category')"
        :headline="$category->name"
        :subline="$category->description"
        :show-pincode="false"
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
                    'label' => $category->parent->name,
                    'url' => route('public.service-categories.show', $category->parent->code),
                ];
            }
            $categoryBreadcrumbItems[] = ['label' => $category->name, 'url' => '#'];
        @endphp
        <x-public.breadcrumbs :items="$categoryBreadcrumbItems" />

        @if ($siblingCategories->isNotEmpty())
            <div class="mt-6 flex flex-wrap gap-2" aria-label="{{ __('Related categories') }}">
                @foreach ($siblingCategories as $sibling)
                    <a href="{{ route('public.service-categories.show', $sibling->code) }}" class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm text-slate-700 hover:border-medca-primary/40 hover:text-medca-primary">
                        {{ $sibling->name }}
                    </a>
                @endforeach
            </div>
        @endif

        @if ($locationRequired)
            <p class="mt-8 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ __('Set your Bangalore pincode to see services available in your area.') }}
            </p>
            <button
                type="button"
                onclick="window.dispatchEvent(new CustomEvent('open-pincode-modal', { detail: { contextPath: window.location.pathname } }))"
                class="mt-4 inline-flex items-center justify-center rounded-xl bg-medca-primary px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-medca-primary-hover"
            >{{ __('Set pincode') }}</button>
        @else
            @if ($pincode)
                <p class="mt-6 text-sm text-slate-600">{{ __('Showing services for pincode :pin', ['pin' => $pincode]) }}</p>
            @endif

            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($services as $service)
                    <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-semibold">
                            <a href="{{ route('public.services.show', $service->service_code) }}" class="text-slate-900 hover:text-medca-primary">{{ $service->title }}</a>
                        </h2>
                        @if ($service->short_summary)
                            <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $service->short_summary }}</p>
                        @endif
                        @if ($service->price_range)
                            <p class="mt-3 text-sm font-medium text-medca-primary">{{ $service->price_range }}</p>
                        @endif
                        @if ($service->categories->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-1">
                                @foreach ($service->categories as $cat)
                                    <span class="text-[10px] uppercase tracking-wide text-slate-500">{{ $cat->name }}</span>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="text-slate-600 sm:col-span-2 lg:col-span-3">{{ __('No published services in this category for your area yet.') }}</p>
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
