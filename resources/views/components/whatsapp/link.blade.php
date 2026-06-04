@props([
    'number' => null,
    'label' => null,
    'class' => '',
])

@php
    $whatsAppService = app(\App\Services\Integrations\WhatsAppClickToChatService::class);
    $resolved = $number instanceof \App\Support\WhatsAppClickNumber
        ? $number
        : (is_array($number) ? \App\Support\WhatsAppClickNumber::fromArray($number) : null);
    $primaryNumber = $whatsAppService->activeNumbers()[0] ?? null;
    $href = $resolved?->waMeUrl()
        ?? $primaryNumber?->waMeUrl()
        ?? $whatsAppService->primaryUrl();
    $buttonName = $label ?? $resolved?->displayName ?? __('WhatsApp');
    $phone = $resolved?->phone ?? $primaryNumber?->phone ?? '';
@endphp

<a
    href="{{ $href }}"
    target="_blank"
    rel="noopener noreferrer"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 '.$class]) }}
    data-whatsapp-track="1"
    data-whatsapp-button="{{ $buttonName }}"
    data-whatsapp-phone="{{ $phone }}"
>
    {{ $slot->isEmpty() ? $buttonName : $slot }}
</a>
