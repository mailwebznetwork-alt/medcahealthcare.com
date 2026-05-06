<div id="site-navigation-root" class="space-y-6" wire:key="site-navigation-root">
    <div class="mom-card p-6">
        <h2 class="text-lg font-semibold text-[var(--text-primary)]">{{ __('Navigation menus') }}</h2>
        <p class="mom-subtext mt-2 max-w-3xl">
            {{ __('Drag live pages between the pool and header/footer. Order is saved automatically when you drop. URLs follow each page’s current slug (updates automatically when you rename a page). Optional labels override the page title in the public header and footer.') }}
        </p>
        @if ($lastSavedAt)
            <p class="mom-micro mt-3 text-[var(--text-muted)]">{{ __('Last saved: :time', ['time' => $lastSavedAt]) }}</p>
        @endif
    </div>

    @if ($livePageCount === 0)
        <div class="mom-card border border-dashed border-[var(--border-panel-soft)] p-6">
            <p class="text-sm text-[var(--text-primary)]">{{ __('There are no published pages yet. Create a page first, then assign it to the header or footer menu.') }}</p>
            <a
                href="{{ route('site-architect.pages.index') }}?create=1"
                class="mom-subtext mt-4 inline-flex font-medium text-mom-gold hover:underline"
            >
                {{ __('Open Pages and create a page') }}
            </a>
        </div>
    @endif

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
                        <label class="mom-micro mt-2 block text-[10px] uppercase tracking-wide text-[var(--text-muted)]" for="nav-label-header-{{ $page->id }}">{{ __('Nav label (optional)') }}</label>
                        <input
                            id="nav-label-header-{{ $page->id }}"
                            type="text"
                            wire:model.blur="customLabels.{{ $page->id }}"
                            wire:blur="saveNavigationLabels"
                            placeholder="{{ $page->title }}"
                            class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-2 py-1.5 text-xs text-[var(--text-primary)] placeholder:text-[var(--text-muted)]"
                        />
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
                        <label class="mom-micro mt-2 block text-[10px] uppercase tracking-wide text-[var(--text-muted)]" for="nav-label-footer-{{ $page->id }}">{{ __('Nav label (optional)') }}</label>
                        <input
                            id="nav-label-footer-{{ $page->id }}"
                            type="text"
                            wire:model.blur="customLabels.{{ $page->id }}"
                            wire:blur="saveNavigationLabels"
                            placeholder="{{ $page->title }}"
                            class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-2 py-1.5 text-xs text-[var(--text-primary)] placeholder:text-[var(--text-muted)]"
                        />
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
