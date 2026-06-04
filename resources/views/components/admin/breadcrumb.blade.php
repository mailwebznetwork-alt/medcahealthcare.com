@props([
    'items' => [],
])

@if ($items !== [])
    <nav class="mom-micro mb-4 flex flex-wrap items-center gap-1.5 text-[var(--text-muted)]" aria-label="{{ __('Breadcrumb') }}">
        @foreach ($items as $index => $item)
            @if ($index > 0)
                <span aria-hidden="true" class="opacity-50">/</span>
            @endif
            @if (! empty($item['url']) && $index < count($items) - 1)
                <a href="{{ $item['url'] }}" class="transition-colors duration-320 ease-premium hover:text-[var(--text-secondary)]">{{ $item['label'] }}</a>
            @else
                <span @class(['text-[var(--text-secondary)]' => $index === count($items) - 1])>{{ $item['label'] }}</span>
            @endif
        @endforeach
    </nav>
@endif
