@props(['disabled' => false, 'variant' => 'default'])

@php
    $baseClass = match ($variant) {
        'mom' => 'border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] text-[var(--text-primary)] placeholder:text-[var(--text-muted)] shadow-mom-inner rounded-mom-chrome focus:border-[rgba(197,160,89,0.28)] focus:ring-1 focus:ring-[rgba(197,160,89,0.22)]',
        'public' => 'rounded-lg border border-slate-300 bg-white text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-[#0046ad] focus:ring-1 focus:ring-[#0046ad]',
        default => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm',
    };
@endphp

<input @disabled($disabled) {{ $attributes->merge(['class' => $baseClass]) }}>
