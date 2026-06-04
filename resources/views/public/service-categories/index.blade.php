@extends('layouts.app')

@section('title', __('Service categories').' — '.config('medca.brand_name'))

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-12 md:py-16">
        <p class="text-sm font-semibold uppercase tracking-widest text-mom-gold">{{ __('Browse by category') }}</p>
        <h1 class="mt-2 text-3xl font-bold text-[var(--text-primary)] md:text-4xl">{{ __('Healthcare services') }}</h1>
        <p class="mt-3 max-w-2xl text-[var(--text-secondary)]">{{ __('Organize discovery by care type. Each service keeps its own SEO and detail page.') }}</p>

        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($categories as $category)
                <a
                    href="{{ route('public.service-categories.show', $category->code) }}"
                    class="group rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] p-6 transition hover:border-mom-gold/40"
                >
                    <h2 class="text-lg font-semibold text-[var(--text-primary)] group-hover:text-mom-gold">{{ $category->name }}</h2>
                    @if ($category->description)
                        <p class="mom-subtext mt-2 line-clamp-3 text-sm">{{ $category->description }}</p>
                    @endif
                    <p class="mt-4 text-xs text-[var(--text-muted)]">
                        {{ trans_choice(':count service|:count services', $category->services_count, ['count' => $category->services_count]) }}
                    </p>
                    @if ($category->children->isNotEmpty())
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($category->children as $child)
                                <li>
                                    <span class="rounded bg-[var(--bg-elevated)] px-2 py-0.5 text-[11px] text-[var(--text-secondary)]">{{ $child->name }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </a>
            @empty
                <p class="text-[var(--text-muted)]">{{ __('Categories will appear here once published in Operations.') }}</p>
            @endforelse
        </div>
    </section>
@endsection
