@props([
    'number' => null,
    'label' => null,
    'class' => '',
    'styled' => true,
])

@php
    use App\Support\BlockContent;

    $whatsAppService = app(\App\Services\Integrations\WhatsAppClickToChatService::class);
    $resolved = $number instanceof \App\Support\WhatsAppClickNumber
        ? $number
        : (is_array($number) ? \App\Support\WhatsAppClickNumber::fromArray($number) : null);
    $primaryNumber = $whatsAppService->activeNumbers()[0] ?? null;
    $href = $resolved?->waMeUrl()
        ?? $primaryNumber?->waMeUrl()
        ?? BlockContent::whatsAppUrl();
    $buttonName = $label ?? $resolved?->displayName ?? __('WhatsApp Us');
    $phone = $resolved?->phone ?? $primaryNumber?->phone ?? '';
    $baseClass = $styled
        ? 'inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800'
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
