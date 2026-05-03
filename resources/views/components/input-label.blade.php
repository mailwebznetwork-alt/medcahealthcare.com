@props(['value', 'variant' => 'default'])

@php
    $baseClass = match ($variant) {
        'mom' => 'block font-medium text-sm text-[var(--text-secondary)]',
        default => 'block font-medium text-sm text-gray-700',
    };
@endphp

<label {{ $attributes->merge(['class' => $baseClass]) }}>
    {{ $value ?? $slot }}
</label>
