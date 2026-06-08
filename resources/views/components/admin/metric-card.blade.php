@props([
    'label',
    'value',
    'hint' => null,
    'href',
    'warn' => false,
])

<a
    href="{{ $href }}"
    {{ $attributes->class([
        'mom-card mom-card-interactive block px-5 py-4 no-underline',
    ]) }}
>
    <p class="mom-micro">{{ $label }}</p>
    <p @class([
        'mom-metric mt-2 leading-none',
        'text-[var(--danger)]' => $warn,
    ])>{{ $value }}</p>
    @if ($hint)
        <p class="mom-subtext mt-2">{{ $hint }}</p>
    @endif
</a>
