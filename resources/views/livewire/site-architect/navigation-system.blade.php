<div id="site-navigation-root" class="space-y-6" wire:key="site-navigation-root">
    <div class="mom-card p-6">
        <h2 class="text-lg font-semibold text-[var(--text-primary)]">{{ __('Navigation menus') }}</h2>
        <p class="mom-subtext mt-2 max-w-3xl">
            {{ __('Drag live pages between the pool and header/footer. Order is saved automatically when you drop. URLs follow each page’s current slug (updates automatically when you rename a page).') }}
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="mom-card flex flex-col p-6">
            <h3 class="mom-section-title mb-3">{{ __('Available (live pages)') }}</h3>
            <p class="mom-micro mb-4">{{ __('Not placed in a menu yet') }}</p>
            <ul
                data-nav-zone="pool"
                class="custom-scrollbar flex min-h-[200px] flex-col gap-2 overflow-y-auto rounded-mom-chrome border border-dashed border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] p-3"
            >
                @foreach ($poolPages as $page)
                    <li
                        wire:key="nav-pool-{{ $page->id }}"
                        data-page-id="{{ $page->id }}"
                        class="cursor-grab rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)] active:cursor-grabbing"
                    >
                        <span class="font-medium">{{ $page->title }}</span>
                        <span class="font-mono mom-micro block text-xs">{{ $page->slug }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mom-card flex flex-col p-6">
            <h3 class="mom-section-title mb-3">{{ __('Header menu') }}</h3>
            <p class="mom-micro mb-4">{{ __('Shown in public site header') }}</p>
            <ul
                data-nav-zone="header"
                class="custom-scrollbar flex min-h-[200px] flex-col gap-2 overflow-y-auto rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] p-3"
            >
                @foreach ($headerPages as $page)
                    <li
                        wire:key="nav-header-{{ $page->id }}"
                        data-page-id="{{ $page->id }}"
                        class="cursor-grab rounded-mom-chrome border border-[rgba(197,160,89,0.35)] bg-[rgba(197,160,89,0.08)] px-3 py-2 text-sm text-[var(--text-primary)] active:cursor-grabbing"
                    >
                        <span class="font-medium">{{ $page->title }}</span>
                        <span class="font-mono mom-micro block text-xs">{{ $page->slug }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mom-card flex flex-col p-6">
            <h3 class="mom-section-title mb-3">{{ __('Footer menu') }}</h3>
            <p class="mom-micro mb-4">{{ __('Shown above copyright on the public site') }}</p>
            <ul
                data-nav-zone="footer"
                class="custom-scrollbar flex min-h-[200px] flex-col gap-2 overflow-y-auto rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] p-3"
            >
                @foreach ($footerPages as $page)
                    <li
                        wire:key="nav-footer-{{ $page->id }}"
                        data-page-id="{{ $page->id }}"
                        class="cursor-grab rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)] active:cursor-grabbing"
                    >
                        <span class="font-medium">{{ $page->title }}</span>
                        <span class="font-mono mom-micro block text-xs">{{ $page->slug }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
