@extends('layouts.app')

@section('title', config('medca.brand_name').' — '.config('app.name'))

@section('content')
    <div id="callback" class="scroll-mt-24" tabindex="-1"></div>
    @auth
        @if (
            auth()->user()->hasModuleAccess(\App\ModuleAccess::DASHBOARD)
            || (auth()->user()->hasModuleAccess(\App\ModuleAccess::GROWTH_CENTER) && Route::has('growth-center.readiness'))
        )
            <div class="border-b border-slate-200 bg-slate-100/90">
                <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                    <p class="text-sm leading-relaxed text-slate-700">
                        {{ __('You are signed in. Growth readiness, SEO health, and competitor tools run in the MarkOnMinds workspace (sidebar layout), not on this public marketing page.') }}
                    </p>
                    <div class="flex flex-shrink-0 flex-wrap gap-2">
                        @if (auth()->user()->hasModuleAccess(\App\ModuleAccess::DASHBOARD))
                            <a
                                href="{{ route('dashboard') }}"
                                class="inline-flex items-center justify-center rounded border border-slate-300 bg-white px-3 py-2 text-center text-[11px] font-bold uppercase tracking-widest text-slate-800 shadow-sm transition hover:bg-slate-50"
                            >
                                {{ __('Open dashboard') }}
                            </a>
                        @endif
                        @if (auth()->user()->hasModuleAccess(\App\ModuleAccess::GROWTH_CENTER) && Route::has('growth-center.readiness'))
                            <a
                                href="{{ route('growth-center.readiness') }}"
                                class="inline-flex items-center justify-center rounded bg-clinical-700 px-3 py-2 text-center text-[11px] font-bold uppercase tracking-widest text-white shadow-sm transition hover:bg-clinical-800"
                            >
                                {{ __('SEO readiness hub') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endauth
    <div class="w-full py-10 md:py-16">
        <p class="mom-micro">{{ config('medca.top_bar_claim') }}</p>
        <h1 class="mom-title-page mt-4 max-w-2xl">{{ config('medca.brand_name') }}</h1>
        <p class="mom-subtext mt-4 max-w-2xl">{{ config('medca.tagline') }}</p>
        <p class="mom-body-text mt-8 max-w-2xl text-[var(--text-secondary)]">
            {{ __('Premium healthcare services across Bengaluru. Explore services, locations, and careers.') }}
        </p>
    </div>
@endsection
