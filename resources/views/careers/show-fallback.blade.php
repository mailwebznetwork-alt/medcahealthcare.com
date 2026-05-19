@extends('layouts.app')

@php
    use Illuminate\Support\Str;

    $pageTitle = $vacancy->seo_title ?: $vacancy->title;
    $metaDescription = $vacancy->seo_description ?: Str::limit(strip_tags((string) ($vacancy->summary ?: $vacancy->description)), 160);
@endphp

@section('title')
    {{ $pageTitle }} — {{ config('app.name') }}
@endsection

@push('meta')
    <meta name="description" content="{{ $metaDescription }}">
    @if ($vacancy->focus_keywords)
        <meta name="keywords" content="{{ $vacancy->focus_keywords }}">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
@endpush

@push('schema')
    @if ($schema !== [])
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
@endpush

@section('content')
    <article class="px-6 py-10 md:px-12">
        <header class="max-w-3xl border-b border-slate-200 pb-8">
            <p class="text-xs text-slate-500">{{ config('careers.organization_name') }}</p>
            <h1 class="mt-2 text-3xl font-semibold text-slate-900">{{ $vacancy->title }}</h1>
            <p class="mt-3 text-sm text-slate-600">
                {{ $vacancy->employment_type->label() }}
                @if ($vacancy->department)
                    · {{ $vacancy->department }}
                @endif
                @if ($vacancy->city)
                    · {{ $vacancy->city }}
                @endif
                @if ($vacancy->area)
                    · {{ $vacancy->area }}
                @endif
                @if ($vacancy->pin_code)
                    · {{ $vacancy->pin_code }}
                @endif
            </p>
            @if ($vacancy->closing_date)
                <p class="mt-4 text-xs text-slate-500">{{ __('Apply before :date', ['date' => $vacancy->closing_date->format('Y-m-d')]) }}</p>
            @endif
        </header>

        <div class="mt-10 grid max-w-5xl grid-cols-1 gap-10 lg:grid-cols-3">
            <div class="space-y-8 lg:col-span-2">
                @if ($vacancy->summary)
                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Overview') }}</h2>
                        <div class="mt-4 whitespace-pre-wrap text-sm text-slate-600">{{ $vacancy->summary }}</div>
                    </section>
                @endif
                @if ($vacancy->description)
                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Role description') }}</h2>
                        <div class="mt-4 whitespace-pre-wrap text-sm text-slate-600">{{ $vacancy->description }}</div>
                    </section>
                @endif
                @if ($vacancy->requirements)
                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Requirements') }}</h2>
                        <div class="mt-4 whitespace-pre-wrap text-sm text-slate-600">{{ $vacancy->requirements }}</div>
                    </section>
                @endif
            </div>
            <aside class="space-y-6">
                @if ($vacancy->whatsapp_apply_url)
                    @include('careers.partials.whatsapp-apply')
                @endif
                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('Apply online') }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Structured intake for the hiring operations engine.') }}</p>
                    <div class="mt-6">
                        @include('careers.partials.apply-form', ['vacancy' => $vacancy])
                    </div>
                </div>
            </aside>
        </div>
    </article>
@endsection
