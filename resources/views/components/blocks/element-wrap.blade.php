@props([
    'tone' => 'light',
    'class' => '',
    'preset' => null,
    'section' => [],
])

@php
    use App\Support\ElementMediaPresenter;

    $toneClass = match ($tone) {
        'dark' => 'bg-slate-900 text-white',
        'muted' => 'bg-slate-50 text-slate-800',
        'brand' => 'medca-hero-gradient text-white',
        default => 'bg-white text-slate-900',
    };
    $sectionData = is_array($section) ? $section : (is_array($blockSection ?? null) ? $blockSection : []);
    $presenter = app(ElementMediaPresenter::class);
    $layoutClass = filled($preset) ? $presenter->layoutClass($preset) : '';
    $mediaClasses = filled($preset) ? implode(' ', $presenter->wrapperClasses($sectionData, $preset)) : '';
    $inlineStyles = filled($preset) ? $presenter->inlineStyles($sectionData, $preset) : [];
    $styleStr = collect($inlineStyles)->map(fn ($v, $k) => $k.':'.$v)->implode(';');
    $mergeAttrs = ['class' => trim('py-12 md:py-16 '.$toneClass.' '.$layoutClass.' '.$mediaClasses.' '.$class)];
    if ($styleStr !== '') {
        $mergeAttrs['style'] = $styleStr;
    }
    $innerClass = filled($preset) ? 'relative z-[1] mx-auto max-w-6xl px-4 sm:px-6 lg:px-8' : 'mx-auto max-w-6xl px-4 sm:px-6 lg:px-8';
@endphp

<x-public.section {{ $attributes->merge($mergeAttrs) }}>
    <div class="{{ $innerClass }}">
        {{ $slot }}
    </div>
</x-public.section>
