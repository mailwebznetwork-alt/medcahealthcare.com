@props([
    'variant' => 'inline',
    'tone' => 'light',
])

@php
    use App\Support\BlockContent;

    $callHref = BlockContent::telHref();
@endphp

<div {{ $attributes->merge([
    'class' => $variant === 'stacked'
        ? 'flex flex-col gap-3 sm:flex-row sm:flex-wrap'
        : 'flex flex-wrap items-center gap-3',
]) }}>
    @if ($callHref !== '')
        <a
            href="{{ $callHref }}"
            data-track="phone_click"
            @class([
                'inline-flex shrink-0 items-center justify-center rounded-xl px-5 py-3 text-sm font-semibold shadow-sm transition',
                'medca-cta-on-hero' => $tone === 'brand',
                'border border-slate-300 bg-white text-slate-900 hover:border-medca-primary/40' => $tone !== 'brand',
            ])
        >
            {{ BlockContent::callUsLabel() }}
        </a>
    @endif
    <x-whatsapp.link class="shrink-0" :label="__('WhatsApp Us')">
        {{ __('WhatsApp Us') }}
    </x-whatsapp.link>
</div>
