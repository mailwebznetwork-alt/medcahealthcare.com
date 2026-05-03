@props(['messages', 'variant' => 'default'])

@php
    $baseClass = match ($variant) {
        'mom' => 'text-sm text-[var(--danger)] space-y-1',
        default => 'text-sm text-red-600 space-y-1',
    };
@endphp

@if ($messages)
    <ul {{ $attributes->merge(['class' => $baseClass]) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
