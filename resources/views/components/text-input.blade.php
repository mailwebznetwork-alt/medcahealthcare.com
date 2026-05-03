@props(['disabled' => false, 'variant' => 'default'])

@php
    $baseClass = match ($variant) {
        'mom' => 'border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] text-[var(--text-primary)] placeholder:text-[var(--text-muted)] shadow-mom-inner rounded-mom-md focus:border-[rgba(160,135,80,0.28)] focus:ring-1 focus:ring-[rgba(160,135,80,0.22)]',
        default => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm',
    };
@endphp

<input @disabled($disabled) {{ $attributes->merge(['class' => $baseClass]) }}>
