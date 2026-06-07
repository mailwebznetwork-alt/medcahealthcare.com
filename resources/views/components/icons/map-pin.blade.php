@props([
    'size' => 15,
])

<svg
    {{ $attributes->merge(['aria-hidden' => 'true']) }}
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.75"
    stroke-linecap="round"
    stroke-linejoin="round"
    width="{{ $size }}"
    height="{{ $size }}"
>
    <path d="M12 21s-6.5-4.35-6.5-10a6.5 6.5 0 1 1 13 0c0 5.65-6.5 10-6.5 10Z" />
    <circle cx="12" cy="11" r="2.25" />
</svg>
