@props([
    'variant' => 'inline',
    'tone' => 'light',
])

@php
    $phoneTel = preg_replace('/\s+/', '', (string) config('medca.phone_tel', ''));
    $phoneDisplay = (string) config('medca.phone_display', '');
@endphp

<div {{ $attributes->merge([
    'class' => $variant === 'stacked'
        ? 'flex flex-col gap-3 sm:flex-row sm:flex-wrap'
        : 'flex flex-wrap items-center gap-3',
]) }}>
    <x-whatsapp.link class="shrink-0" :label="__('WhatsApp Us')">
        {{ __('WhatsApp Us') }}
    </x-whatsapp.link>
    @if ($phoneTel !== '')
        <a
            href="tel:{{ $phoneTel }}"
            data-track="phone_click"
            @class([
                'inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-semibold shadow-sm transition',
                'medca-cta-on-hero' => $tone === 'brand',
                'border border-slate-300 bg-white text-slate-900 hover:border-medca-primary/40' => $tone !== 'brand',
            ])
        >
            {{ __('Call Now') }}
        </a>
    @endif
</div>
