@props(['variant' => 'default'])

@php
    $baseClass = match ($variant) {
        'mom' => 'mom-cta-primary focus:outline-none',
        default => 'inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150',
    };
@endphp

<button {{ $attributes->merge(['type' => 'submit', 'class' => $baseClass]) }}>
    {{ $slot }}
</button>
