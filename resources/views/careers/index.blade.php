@extends('layouts.app')

@section('title', __('Careers').' — '.config('app.name'))

@section('content')
    <header class="border-b border-[rgba(255,255,255,0.045)] px-6 py-10 md:px-12">
        <p class="mom-micro text-[var(--text-muted)]">{{ config('careers.organization_name') }}</p>
        <h1 class="mom-title-page mt-2">{{ __('Careers') }}</h1>
        <p class="mom-subtext mt-3 max-w-2xl">{{ __('Open roles across our operating footprint. Structured listings with clear locations and application paths.') }}</p>
    </header>

    <main class="px-6 py-10 md:px-12">
        @if ($vacancies->isEmpty())
            <div class="mom-card p-10 text-center">
                <p class="mom-section-title">{{ __('No open roles right now') }}</p>
                <p class="mom-subtext mt-2">{{ __('Please check again soon.') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                @foreach ($vacancies as $vacancy)
                    <article class="mom-card mom-card-interactive p-6">
                        <h2 class="mom-section-title">
                            <a href="{{ route('careers.show', ['slug' => $vacancy->slug]) }}" class="hover:text-mom-gold">{{ $vacancy->title }}</a>
                        </h2>
                        <p class="mom-subtext mt-2">
                            {{ $vacancy->department ?? __('Operations') }}
                            @if ($vacancy->city)
                                · {{ $vacancy->city }}
                            @endif
                            @if ($vacancy->pin_code)
                                · {{ $vacancy->pin_code }}
                            @endif
                        </p>
                        @if ($vacancy->summary)
                            <p class="mom-body-text mt-4 line-clamp-3 text-[var(--text-secondary)]">{{ $vacancy->summary }}</p>
                        @endif
                        <p class="mom-micro mt-4 text-[var(--text-muted)]">{{ $vacancy->employment_type->label() }}</p>
                    </article>
                @endforeach
            </div>
            <div class="mt-10">
                {{ $vacancies->links() }}
            </div>
        @endif
    </main>
@endsection
