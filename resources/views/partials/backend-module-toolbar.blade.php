<div class="mom-module-toolbar-host">
    <div class="mom-module-toolbar-host__inner py-4">
        <nav class="flex flex-wrap gap-3" aria-label="{{ $title }}">
            <a
                href="{{ route('dashboard') }}"
                class="mom-toolbar-pill mom-toolbar-pill-muted"
            >{{ __('Dashboard') }}</a>
            <span class="mom-toolbar-pill mom-toolbar-pill-active cursor-default" aria-current="page">{{ $title }}</span>
        </nav>
    </div>
</div>
