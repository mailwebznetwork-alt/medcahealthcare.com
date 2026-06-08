@props([
    'tag' => 'div',
    'interactive' => false,
    'href' => null,
    'title' => null,
    'flush' => false,
    'padding' => 'p-6',
])

@php
    if ($href) {
        $tag = 'a';
        $interactive = true;
    }
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class([
        'mom-card',
        'mom-card-interactive' => $interactive || $href,
        'block no-underline' => $href,
        $padding => ! $flush,
        'p-0' => $flush && $padding === 'p-6',
    ]) }}
>
    @if ($title)
        <p class="mom-micro">{{ $title }}</p>
    @endif
    {{ $slot }}
</{{ $tag }}>
