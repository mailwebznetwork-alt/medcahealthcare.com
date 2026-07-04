@props([
    'number' => null,
    'label' => null,
    'class' => '',
    'styled' => true,
])

@php
    $whatsAppService = app(\App\Services\Integrations\WhatsAppClickToChatService::class);
    $canonicalPhone = $whatsAppService->canonicalPhone();
    $canonicalUrl = $whatsAppService->canonicalUrl();
    $resolved = $number instanceof \App\Support\WhatsAppClickNumber
        ? $number
        : (is_array($number) ? \App\Support\WhatsAppClickNumber::fromArray($number) : null);
    // Only honour an explicit number when it matches the brand line.
    $href = ($resolved !== null && $resolved->phone === $canonicalPhone)
        ? $resolved->waMeUrl()
        : $canonicalUrl;
    $buttonName = $label ?? $resolved?->displayName ?? __('WhatsApp Us');
    $phone = $canonicalPhone;
    $baseClass = $styled
        ? 'medca-whatsapp-btn shadow-sm'
        : 'inline-flex items-center gap-2';
@endphp

<a
    href="{{ $href }}"
    target="_blank"
    rel="noopener noreferrer"
    {{ $attributes->merge(['class' => trim($baseClass.' '.$class)]) }}
    data-whatsapp-track="1"
    data-whatsapp-button="{{ $buttonName }}"
    data-whatsapp-phone="{{ $phone }}"
>
    {{ $slot->isEmpty() ? $buttonName : $slot }}
</a>
