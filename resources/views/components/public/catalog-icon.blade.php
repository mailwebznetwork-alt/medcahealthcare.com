@props([
    'model' => null,
    'name' => null,
    'size' => 'md',
    'label' => null,
    'badge' => true,
])

@php
    $badgeClass = match ($size) {
        'sm', 'xs' => 'medca-catalog-icon-badge medca-catalog-icon-badge--sm',
        'lg', 'xl' => 'medca-catalog-icon-badge medca-catalog-icon-badge--lg',
        default => 'medca-catalog-icon-badge',
    };
@endphp

@if ($badge)
    <span {{ $attributes->class([$badgeClass]) }}>
        <x-public.line-icon :model="$model" :name="$name" :size="$size" :label="$label" />
    </span>
@else
    <x-public.line-icon :model="$model" :name="$name" :size="$size" :label="$label" {{ $attributes }} />
@endif
