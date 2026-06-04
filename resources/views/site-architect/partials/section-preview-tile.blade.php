@props(['previewKey' => 'default'])

@php
    $styles = match ($previewKey) {
        'hero' => 'from-indigo-900/80 to-sky-900/40',
        'cta' => 'from-amber-700/50 to-orange-900/30',
        'faq' => 'from-slate-700/60 to-slate-900/40',
        'gallery' => 'from-purple-800/40 to-pink-900/30',
        'testimonials' => 'from-emerald-800/40 to-teal-900/30',
        'form' => 'from-blue-800/40 to-cyan-900/30',
        'map', 'locations' => 'from-green-800/40 to-lime-900/20',
        'statistics' => 'from-violet-800/40 to-indigo-900/30',
        'process' => 'from-orange-800/30 to-red-900/20',
        'doctors' => 'from-sky-800/40 to-blue-900/30',
        'services' => 'from-cyan-800/30 to-blue-900/30',
        default => 'from-[var(--bg-elevated)] to-[var(--bg-card-matte)]',
    };
@endphp
<div {{ $attributes->merge(['class' => 'h-20 w-full rounded-lg bg-gradient-to-br '.$styles.' border border-[var(--border-panel-soft)]']) }} aria-hidden="true"></div>
