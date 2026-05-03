@props(['variant' => 'default'])

@php
    $baseClass = match ($variant) {
        'mom' => 'inline-flex items-center justify-center rounded-mom-md border border-[rgba(226,92,92,0.35)] bg-[rgba(226,92,92,0.12)] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-[var(--danger)] transition-all duration-320 ease-premium hover:border-[rgba(226,92,92,0.55)] hover:bg-[rgba(226,92,92,0.18)] focus:outline-none focus:ring-2 focus:ring-[rgba(226,92,92,0.45)] focus:ring-offset-2 focus:ring-offset-[#070707]',
        default => 'inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150',
    };
@endphp

<button {{ $attributes->merge(['type' => 'submit', 'class' => $baseClass]) }}>
    {{ $slot }}
</button>
