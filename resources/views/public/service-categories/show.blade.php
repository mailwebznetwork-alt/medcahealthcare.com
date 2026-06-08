@extends('layouts.app')

@section('title', $category->name.' — '.config('medca.brand_name'))

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-12 md:py-16">
        <nav class="text-sm text-[var(--text-muted)]" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('public.service-categories.index') }}" class="hover:text-mom-gold">{{ __('Categories') }}</a>
            @if ($category->parent)
                <span class="mx-2">/</span>
                <a href="{{ route('public.service-categories.show', $category->parent->code) }}" class="hover:text-mom-gold">{{ $category->parent->name }}</a>
            @endif
            <span class="mx-2">/</span>
            <span class="text-[var(--text-secondary)]">{{ $category->name }}</span>
        </nav>

        <h1 class="mt-4 text-3xl font-bold text-[var(--text-primary)] md:text-4xl">{{ $category->name }}</h1>
        @if ($category->description)
            <p class="mt-4 max-w-3xl text-lg text-[var(--text-secondary)]">{{ $category->description }}</p>
        @endif

        @if ($siblingCategories->isNotEmpty())
            <div class="mt-8 flex flex-wrap gap-2" aria-label="{{ __('Related categories') }}">
                @foreach ($siblingCategories as $sibling)
                    <a href="{{ route('public.service-categories.show', $sibling->code) }}" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-1.5 text-sm text-[var(--text-secondary)] hover:border-mom-gold/40 hover:text-mom-gold">
                        {{ $sibling->name }}
                    </a>
                @endforeach
            </div>
        @endif

        @if ($locationRequired)
            <p class="mt-8 rounded-mom-chrome border border-[rgba(226,184,92,0.35)] bg-[rgba(226,184,92,0.08)] px-4 py-3 text-sm text-[var(--warning)]">
                {{ __('Set your Bangalore pincode to see services available in your area.') }}
            </p>
            <button
                type="button"
                onclick="window.dispatchEvent(new CustomEvent('open-pincode-modal', { detail: { contextPath: window.location.pathname } }))"
                class="mom-cta-primary mt-4"
            >{{ __('Set pincode') }}</button>
        @else
            @if ($pincode)
                <p class="mt-6 text-sm text-[var(--text-muted)]">{{ __('Showing services for pincode :pin', ['pin' => $pincode]) }}</p>
            @endif

            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($services as $service)
                    <article class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] p-5">
                        <h2 class="text-lg font-semibold">
                            <a href="{{ route('public.services.show', $service->service_code) }}" class="text-[var(--text-primary)] hover:text-mom-gold">{{ $service->title }}</a>
                        </h2>
                        @if ($service->short_summary)
                            <p class="mt-2 line-clamp-3 text-sm text-[var(--text-secondary)]">{{ $service->short_summary }}</p>
                        @endif
                        @if ($service->price_range)
                            <p class="mt-3 text-sm font-medium text-mom-gold">{{ $service->price_range }}</p>
                        @endif
                        @if ($service->categories->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-1">
                                @foreach ($service->categories as $cat)
                                    <span class="text-[10px] uppercase tracking-wide text-[var(--text-muted)]">{{ $cat->name }}</span>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="text-[var(--text-muted)] sm:col-span-2 lg:col-span-3">{{ __('No published services in this category for your area yet.') }}</p>
                @endforelse
            </div>

            @if ($services->hasPages())
                <div class="mt-10">
                    {{ $services->links() }}
                </div>
            @endif
        @endif
    </section>
@endsection
