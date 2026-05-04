@php
    $pagesActive = request()->routeIs('site-architect.pages.*');
    $blogsActive = request()->routeIs('site-architect.blogs.*');
    $blockFactoryActive = request()->routeIs('site-architect.block-factory.*');
    $mediaActive = request()->routeIs('site-architect.media.*');
@endphp

<nav class="flex flex-wrap gap-0" aria-label="{{ __('Site Architect workspaces') }}">
    <a
        href="{{ route('site-architect.pages.index') }}"
        @class([
            'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $pagesActive,
            'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => ! $pagesActive,
        ])
    >{{ __('Pages') }}</a>
    <a
        href="{{ route('site-architect.blogs.index') }}"
        @class([
            'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $blogsActive,
            'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => ! $blogsActive,
        ])
    >{{ __('Blogs') }}</a>
    <a
        href="{{ route('site-architect.block-factory.index') }}"
        @class([
            'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $blockFactoryActive,
            'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => ! $blockFactoryActive,
        ])
    >{{ __('Block Factory') }}</a>
    <a
        href="{{ route('site-architect.media.index') }}"
        @class([
            'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $mediaActive,
            'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => ! $mediaActive,
        ])
    >{{ __('Media') }}</a>
</nav>
