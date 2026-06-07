@props([
    'numbers' => null,
    'columns' => 'md:grid-cols-2 lg:grid-cols-3',
])

@php
    $items = $numbers ?? ($whatsAppNumbers ?? app(\App\Services\Integrations\WhatsAppClickToChatService::class)->activeNumbers());
@endphp

@if ($items !== [])
    <div {{ $attributes->merge(['class' => 'grid gap-4 '.$columns]) }}>
        @foreach ($items as $waNumber)
            <article class="mom-card flex flex-col p-5">
                <h3 class="mom-section-title text-base">{{ $waNumber->displayName }}</h3>
                @if ($waNumber->defaultMessage !== '')
                    <p class="mom-subtext mt-2 line-clamp-2">{{ $waNumber->defaultMessage }}</p>
                @endif
                <x-whatsapp.link
                    :number="$waNumber"
                    class="mt-4 w-fit px-4 py-2"
                    :label="__('WhatsApp Us')"
                >
                    {{ __('WhatsApp Us') }}
                </x-whatsapp.link>
            </article>
        @endforeach
    </div>
@endif
