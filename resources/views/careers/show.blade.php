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
        <header class="max-w-3xl border-b border-[rgba(255,255,255,0.045)] pb-8">
            <p class="mom-micro text-[var(--text-muted)]">{{ config('careers.organization_name') }}</p>
            <h1 class="mom-title-page mt-2">{{ $vacancy->title }}</h1>
            <p class="mom-subtext mt-3">
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
                <p class="mom-micro mt-4 text-[var(--text-muted)]">{{ __('Apply before :date', ['date' => $vacancy->closing_date->format('Y-m-d')]) }}</p>
            @endif
        </header>

        @if (session('status') === 'application-received')
            <p class="mom-body-text mt-8 text-[var(--success)]" role="status">{{ __('Thank you — your application was received.') }}</p>
        @endif

        <div class="mt-10 grid max-w-5xl grid-cols-1 gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-8">
                @if ($vacancy->summary)
                    <section class="mom-card p-6">
                        <h2 class="mom-section-title">{{ __('Overview') }}</h2>
                        <div class="mom-body-text mt-4 whitespace-pre-wrap text-[var(--text-secondary)]">{{ $vacancy->summary }}</div>
                    </section>
                @endif
                @if ($vacancy->description)
                    <section class="mom-card p-6">
                        <h2 class="mom-section-title">{{ __('Role description') }}</h2>
                        <div class="mom-body-text mt-4 whitespace-pre-wrap text-[var(--text-secondary)]">{{ $vacancy->description }}</div>
                    </section>
                @endif
                @if ($vacancy->requirements)
                    <section class="mom-card p-6">
                        <h2 class="mom-section-title">{{ __('Requirements') }}</h2>
                        <div class="mom-body-text mt-4 whitespace-pre-wrap text-[var(--text-secondary)]">{{ $vacancy->requirements }}</div>
                    </section>
                @endif
            </div>
            <aside class="space-y-6">
                @if ($vacancy->whatsapp_apply_url)
                    <div class="mom-card p-6">
                        <h2 class="mom-section-title">{{ __('Apply via WhatsApp') }}</h2>
                        <p class="mom-subtext mt-2">{{ __('Continue in WhatsApp for recruiter routing and ATS tracking.') }}</p>
                        <a
                            href="{{ $vacancy->whatsapp_apply_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            onclick="if(typeof gtag==='function'){gtag('event','whatsapp_click');}"
                            class="mom-cta-primary mt-4 w-full"
                        >{{ __('Open WhatsApp') }}</a>
                    </div>
                @endif
                <div class="mom-card p-6">
                    <h2 class="mom-section-title">{{ __('Apply online') }}</h2>
                    <p class="mom-subtext mt-2">{{ __('Structured intake for the hiring operations engine.') }}</p>
                    <form method="post" action="{{ route('careers.apply', ['slug' => $vacancy->slug]) }}" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="full_name" :value="__('Full name')" variant="mom" />
                            <x-text-input id="full_name" name="full_name" type="text" class="mt-2 block w-full" :value="old('full_name')" required variant="mom" />
                            <x-input-error class="mt-2" :messages="$errors->get('full_name')" variant="mom" />
                        </div>
                        <div>
                            <x-input-label for="email" :value="__('Email')" variant="mom" />
                            <x-text-input id="email" name="email" type="email" class="mt-2 block w-full" :value="old('email')" required variant="mom" />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" variant="mom" />
                        </div>
                        <div>
                            <x-input-label for="phone" :value="__('Phone')" variant="mom" />
                            <x-text-input id="phone" name="phone" type="tel" class="mt-2 block w-full" :value="old('phone')" required variant="mom" />
                            <x-input-error class="mt-2" :messages="$errors->get('phone')" variant="mom" />
                        </div>
                        <div>
                            <x-input-label for="city" :value="__('City (optional)')" variant="mom" />
                            <x-text-input id="city" name="city" type="text" class="mt-2 block w-full" :value="old('city')" variant="mom" />
                        </div>
                        <div>
                            <x-input-label for="pin_code" :value="__('PIN code (optional)')" variant="mom" />
                            <x-text-input id="pin_code" name="pin_code" type="text" class="mt-2 block w-full" :value="old('pin_code')" variant="mom" />
                        </div>
                        <div>
                            <x-input-label for="cover_message" :value="__('Message')" variant="mom" />
                            <textarea id="cover_message" name="cover_message" rows="4" class="mt-2 block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">{{ old('cover_message') }}</textarea>
                        </div>
                        <input type="hidden" name="source" value="web" />
                        <label class="flex items-start gap-2 text-[13px] text-[var(--text-secondary)]">
                            <input type="checkbox" name="whatsapp_click" value="1" class="mt-1 h-4 w-4 rounded border-[rgba(255,255,255,0.12)] bg-[rgba(28,22,18,0.75)] text-mom-gold" @checked(old('whatsapp_click')) />
                            <span>{{ __('I reached this role through a WhatsApp link') }}</span>
                        </label>
                        <x-primary-button variant="mom" type="submit">{{ __('Submit application') }}</x-primary-button>
                    </form>
                </div>
            </aside>
        </div>
    </article>
@endsection
