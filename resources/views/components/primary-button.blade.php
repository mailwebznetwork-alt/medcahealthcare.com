@props(['variant' => 'default'])

@php
    $baseClass = match ($variant) {
        'mom' => 'inline-flex items-center justify-center rounded-mom-md border border-[rgba(212,169,95,0.28)] bg-[linear-gradient(180deg,rgba(212,169,95,0.22),rgba(212,169,95,0.12))] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-[#0a0a0a] shadow-[0_0_24px_rgba(212,169,95,0.15)] transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.4)] hover:shadow-[0_0_32px_rgba(212,169,95,0.22)] focus:outline-none focus:ring-2 focus:ring-[rgba(212,169,95,0.35)] focus:ring-offset-2 focus:ring-offset-[var(--bg-app)]',
        default => 'inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150',
    };
@endphp

<button {{ $attributes->merge(['type' => 'submit', 'class' => $baseClass]) }}>
    {{ $slot }}
</button>
