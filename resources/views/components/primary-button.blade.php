@props(['variant' => 'default'])

@php
    $baseClass = match ($variant) {
        'mom' => 'mom-cta-primary focus:outline-none',
        'public' => 'inline-flex items-center justify-center rounded-lg border border-transparent bg-[#0046ad] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#003d94] focus:outline-none focus:ring-2 focus:ring-[#0046ad] focus:ring-offset-2 focus:ring-offset-white',
        default => 'inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150',
    };
@endphp

<button {{ $attributes->merge(['type' => 'submit', 'class' => $baseClass]) }}>
    {{ $slot }}
</button>
