@props([
    'slug' => null,
    'field' => 'headline',
    'tag' => 'h2',
    'class' => '',
])

@php
    use App\Support\BlockContent;
    $blockSlug = $slug ?? ($blockSlug ?? '');
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $text = $blockSlug !== '' ? BlockContent::get($settings, $blockSlug, $field) : '';
@endphp

@if ($text !== '')
    @if ($tag === 'h1')
        <h1 {{ $attributes->merge(['class' => $class]) }}>{{ $text }}</h1>
    @elseif ($tag === 'p')
        <p {{ $attributes->merge(['class' => $class]) }}>{{ $text }}</p>
    @else
        <h2 {{ $attributes->merge(['class' => $class]) }}>{{ $text }}</h2>
    @endif
@endif
