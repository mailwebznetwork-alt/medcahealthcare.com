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
            class="medca-call-btn shrink-0 shadow-sm"
        >
            {{ BlockContent::callUsLabel() }}
        </a>
    @endif
    <x-whatsapp.link class="shrink-0 shadow-sm" :label="__('WhatsApp Us')">
        {{ __('WhatsApp Us') }}
    </x-whatsapp.link>
</div>
