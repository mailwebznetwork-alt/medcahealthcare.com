@props([
    'media' => null,
    'mediaId' => null,
    'path' => null,
    'preset' => null,
    'section' => [],
    'class' => '',
    'sizes' => '(max-width: 768px) 100vw, 1200px',
])

@php
    use App\Models\Media;
    use App\Support\ElementMediaPresenter;

    $presenter = app(ElementMediaPresenter::class);
    $resolved = $media instanceof Media ? $media : null;
    if ($resolved === null && $mediaId) {
        $resolved = Media::query()->find((int) $mediaId);
    }
    if ($resolved === null && $path) {
        $resolved = app(\App\Services\Media\MediaReferenceResolver::class)->findByPath((string) $path);
    }
    $section = is_array($section) ? $section : [];
    $classes = implode(' ', $presenter->wrapperClasses($section, $preset));
    $layoutClass = $presenter->layoutClass($preset);
    $styles = $presenter->inlineStyles($section, $preset);
    $styleStr = collect($styles)->map(fn ($v, $k) => $k.':'.$v)->implode(';');
@endphp

@if ($resolved && $resolved->file_type === 'image')
    @php $sources = $presenter->responsiveSources($resolved, $sizes); @endphp
    <picture {{ $attributes->merge(['class' => trim($classes.' '.$layoutClass.' '.$class), 'style' => $styleStr !== '' ? $styleStr : null]) }}>
        @if ($sources['avif'])
            <source type="image/avif" srcset="{{ $sources['avif'] }}" sizes="{{ $sources['sizes'] }}" />
        @endif
        @if ($sources['srcset'] !== '')
            <source type="image/webp" srcset="{{ $sources['srcset'] }}" sizes="{{ $sources['sizes'] }}" />
        @endif
        <img
            src="{{ $sources['src'] }}"
            @if ($sources['srcset'] !== '') srcset="{{ $sources['srcset'] }}" sizes="{{ $sources['sizes'] }}" @endif
            alt="{{ $sources['alt'] }}"
            loading="lazy"
            decoding="async"
            class="medca-responsive-media__img"
        />
    </picture>
@elseif ($path)
    @php $url = \App\Support\BlockMediaUrl::resolve((string) $path); @endphp
    @if ($url)
        <img src="{{ $url }}" alt="" loading="lazy" decoding="async" {{ $attributes->merge(['class' => trim($classes.' '.$class)]) }} />
    @endif
@endif
