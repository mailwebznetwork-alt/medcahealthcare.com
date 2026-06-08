@props(['items' => []])

@if (is_array($items) && $items !== [] && ! config('medca.hide_visual_breadcrumbs', true))
<nav aria-label="{{ __('Breadcrumb') }}" class="medca-visual-breadcrumbs mb-6 text-sm text-slate-600">
    <ol class="flex flex-wrap items-center gap-1">
        @foreach ($items as $index => $item)
            @if ($index > 0)
                <li aria-hidden="true" class="text-slate-400">/</li>
            @endif
            <li>
                @if ($loop->last)
                    <span class="font-medium text-slate-900" aria-current="page">{{ $item['label'] ?? '' }}</span>
                @else
                    <a href="{{ $item['url'] ?? '#' }}" class="hover:text-medca-primary hover:underline">{{ $item['label'] ?? '' }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@endif
