@props(['variant' => 'default'])

@php
    $baseClass = match ($variant) {
        'mom' => 'inline-flex items-center justify-center rounded-mom-md border border-[rgba(255,255,255,0.045)] bg-[rgba(255,255,255,0.03)] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-[var(--text-secondary)] shadow-mom-inner transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.16)] hover:text-[var(--text-primary)] focus:outline-none focus:ring-2 focus:ring-[rgba(212,169,95,0.2)] focus:ring-offset-2 focus:ring-offset-[var(--bg-app)] disabled:opacity-25',
        default => 'inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150',
    };
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => $baseClass]) }}>
    {{ $slot }}
</button>
