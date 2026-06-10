@php
    $children = is_array($item['children'] ?? null) ? $item['children'] : [];
    $href = $item['href'] ?? '#';
    $label = $item['label'] ?? '';
    $depth = $depth ?? 0;
@endphp

<li role="none" @class(['medca-nav-flyout-parent' => $children !== []])>
    @if ($children !== [])
        <div class="medca-nav-flyout-trigger">
            @if ($href && $href !== '#')
                <a href="{{ $href }}" class="font-medium hover:underline" role="menuitem">{{ $label }}</a>
            @else
                <span class="font-medium">{{ $label }}</span>
            @endif
            <span class="text-xs text-slate-400" aria-hidden="true">›</span>
        </div>
        <ul class="medca-nav-flyout" role="menu">
            @foreach ($children as $child)
                @include('components.public.nav-dropdown-item', ['item' => $child, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @else
        <a href="{{ $href }}" role="menuitem">{{ $label }}</a>
    @endif
</li>
