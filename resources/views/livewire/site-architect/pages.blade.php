<div>
    @if (session('status'))
        <div class="mom-card mb-6 border border-[rgba(98,195,120,0.28)] bg-[rgba(98,195,120,0.08)] px-4 py-3 text-sm text-[var(--success)]" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($mode === 'list')
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-[var(--text-primary)]">{{ __('Pages') }}</h2>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    wire:click="syncServiceDetailPages"
                    wire:confirm="{{ __('Create or update Site Architect pages for every service (slug service-{code})?') }}"
                    class="inline-flex rounded-mom-chrome border border-[rgba(197,160,89,0.28)] bg-[rgba(197,160,89,0.1)] px-4 py-2 text-sm font-medium text-mom-gold"
                >
                    {{ __('Sync service pages') }}
                </button>
                <a
                    href="{{ route('site-architect.pages.index') }}?create=1"
                    wire:click.prevent="startCreate"
                    role="button"
                    class="inline-flex cursor-pointer rounded-mom-chrome border border-[rgba(197,160,89,0.28)] bg-[rgba(197,160,89,0.1)] px-4 py-2 text-sm font-medium text-mom-gold no-underline"
                >
                    {{ __('Create page') }}
                </a>
            </div>
        </div>

        <p class="mom-subtext mb-4 max-w-3xl text-sm">{{ __('Pages are grouped: Web, Service, Location, Blog, Landing. Public service URLs use /services/{code} — not /p/.') }}</p>
        @if (! empty($pageCategoryCounts))
            <p class="mb-4 flex flex-wrap gap-2 text-xs text-[var(--text-muted)]">
                @foreach (\App\Enums\PageCategory::cases() as $cat)
                    <span class="rounded-full border border-[var(--border-panel-soft)] px-2 py-0.5">{{ $cat->label() }}: {{ $pageCategoryCounts[$cat->value] ?? 0 }}</span>
                @endforeach
            </p>
        @endif

        <div class="mb-4 flex flex-wrap gap-4">
            <div class="max-w-md flex-1 min-w-[12rem]">
                <label for="pages-search" class="mom-micro text-[var(--text-muted)]">{{ __('Search pages') }}</label>
                <input
                    id="pages-search"
                    type="search"
                    wire:model.live.debounce.300ms="pageSearch"
                    placeholder="{{ __('Title or slug — e.g. service-medical-lab') }}"
                    class="mom-input mt-1 w-full text-sm"
                    autocomplete="off"
                />
            </div>
            <div class="w-48">
                <label for="pages-category" class="mom-micro text-[var(--text-muted)]">{{ __('Category') }}</label>
                <select id="pages-category" wire:model.live="pageCategoryFilter" class="mom-input mt-1 w-full text-sm">
                    <option value="all">{{ __('All categories') }}</option>
                    @foreach (\App\Enums\PageCategory::cases() as $cat)
                        <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mb-3 flex flex-wrap gap-2 text-xs">
            <button type="button" wire:click="selectAllVisibleRows({{ $pages->pluck('id')->values()->toJson() }})" class="text-mom-gold hover:underline">{{ __('Select all visible') }}</button>
            <button type="button" wire:click="selectAllFilteredRows" class="text-mom-gold hover:underline">{{ __('Select all filtered results') }}</button>
            <button type="button" wire:click="deselectAllRows" class="text-[var(--text-muted)] hover:underline">{{ __('Deselect all') }}</button>
        </div>

        <x-bulk.selection-toolbar :count="$this->bulkSelectedCount()" :actions="['modify', 'duplicate', 'delete', 'publish', 'unpublish', 'export']" />

        <div class="mom-card overflow-x-auto p-0">
            <table class="mom-table w-full min-w-[640px] text-left text-sm">
                <thead>
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input
                                type="checkbox"
                                class="rounded border-[var(--border-panel-soft)]"
                                aria-label="{{ __('Select all visible') }}"
                                wire:click="selectAllVisibleRows({{ $pages->pluck('id')->values()->toJson() }})"
                            />
                        </th>
                        <th class="px-4 py-3">{{ __('Page name') }}</th>
                        <th class="px-4 py-3">{{ __('Slug') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3">{{ __('Preview') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pages as $page)
                        @php
                            $pageCategory = $page->page_category ?? app(\App\Services\Operations\PageCategoryResolver::class)->resolve($page);
                            $locRow = $pageCategory === \App\Enums\PageCategory::Location
                                ? \App\Models\ServiceLocationPage::query()->where('page_id', $page->id)->first()
                                : null;
                            $serviceCode = $serviceCodesByPageId[$page->id]
                                ?? ($locRow?->service?->service_code)
                                ?? \App\Services\Operations\ServiceDetailPageProvisioner::serviceCodeFromPageSlug($page->slug);
                            $locationPublicUrl = ($locRow !== null && $page->is_active) ? $locRow->publicUrl() : null;
                            $previewUrl = $locationPublicUrl ?? route('site-architect.pages.preview', $page);
                        @endphp
                        <tr wire:key="page-row-{{ $page->id }}">
                            <td class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    class="rounded border-[var(--border-panel-soft)]"
                                    wire:click="toggleBulkRow({{ $page->id }})"
                                    @checked($this->isBulkRowSelected($page->id))
                                    aria-label="{{ __('Select row') }}"
                                />
                            </td>
                            <td class="px-4 py-3 font-medium text-[var(--text-primary)]">
                                {{ $page->title }}
                                <span class="ml-2 rounded-full bg-[rgba(255,255,255,0.06)] px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-[var(--text-muted)]">{{ $pageCategory->label() }}</span>
                                @if ($serviceCode !== null && $pageCategory === \App\Enums\PageCategory::Service)
                                    <span class="ml-1 rounded-full bg-[rgba(197,160,89,0.15)] px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-mom-gold">{{ __('Detail') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-[var(--text-muted)]">{{ $page->slug }}</td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    wire:click="toggleActive({{ $page->id }})"
                                    class="text-xs font-semibold uppercase tracking-wide {{ $page->is_active ? 'text-[var(--success)]' : 'text-[var(--text-muted)]' }}"
                                >
                                    {{ $page->is_active ? __('Live') : __('Off') }}
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <a
                                    href="{{ $previewUrl }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-mom-gold hover:underline"
                                >{{ __('Preview') }}</a>
                                @if ($page->is_active)
                                    <span class="text-[var(--text-muted)]">·</span>
                                    @if ($locationPublicUrl !== null && Route::has('public.services.location'))
                                        <a href="{{ $locationPublicUrl }}" target="_blank" rel="noopener" class="text-mom-gold hover:underline">{{ __('Location URL') }}</a>
                                    @elseif ($serviceCode !== null && Route::has('public.services.show'))
                                        <a
                                            href="{{ route('public.services.show', $serviceCode) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="text-mom-gold hover:underline"
                                        >{{ __('Service URL') }}</a>
                                    @else
                                        <a
                                            href="{{ route('pages.public', ['slug' => $page->slug]) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="text-mom-gold hover:underline"
                                        >{{ __('Live') }}</a>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" wire:click="startEdit({{ $page->id }})" class="mr-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">{{ __('Edit') }}</button>
                                <button type="button" wire:click="duplicatePage({{ $page->id }})" class="mr-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">{{ __('Duplicate') }}</button>
                                <button
                                    type="button"
                                    wire:click="deletePage({{ $page->id }})"
                                    wire:confirm="{{ __('Delete this page?') }}"
                                    class="text-[var(--danger)] hover:underline"
                                >{{ __('Delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center">
                                <p class="text-[var(--text-primary)] font-medium">{{ __('No pages yet') }}</p>
                                <p class="mom-subtext mx-auto mt-2 max-w-md text-sm">{{ __('Create your first page, add blocks (sections), edit copy in Blocks Studio, then turn Live on.') }}</p>
                                <a
                                    href="{{ route('site-architect.pages.index') }}?create=1"
                                    wire:click.prevent="startCreate"
                                    class="mt-4 inline-flex rounded-mom-chrome bg-[var(--accent-gold)] px-4 py-2 text-sm font-semibold text-[#120f0d] no-underline"
                                >{{ __('Create page') }}</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $pages->links() }}
        </div>
    @else
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-[var(--text-primary)]">{{ $editingId ? __('Edit page') : __('New page') }}</h2>
            <button type="button" wire:click="cancelForm" class="mom-subtext text-[var(--text-muted)] hover:text-[var(--text-primary)]">{{ __('Back to list') }}</button>
        </div>

        <div class="space-y-8">
            @if ($productionPreviewUrl)
                <section class="mom-card overflow-hidden p-0" aria-label="{{ __('Production preview') }}">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[color:var(--border-tabstrip-divider)] px-4 py-3">
                        <div>
                            <h3 class="mom-section-title">{{ __('Preview (production path)') }}</h3>
                            <p class="mom-subtext mt-1 max-w-2xl">{{ __('Same render as public: ContentParser + layouts.app. Save the page, then refresh preview if you changed structure or SEO.') }}</p>
                        </div>
                        <a href="{{ $productionPreviewUrl }}" target="_blank" rel="noopener" class="mom-cta-compact mom-cta-primary">{{ __('Open full preview') }}</a>
                    </div>
                    <iframe
                        wire:key="page-preview-{{ $previewRefreshNonce }}-{{ $editingId }}"
                        src="{{ $productionPreviewUrl }}{{ str_contains($productionPreviewUrl, '?') ? '&' : '?' }}_preview={{ $previewRefreshNonce }}"
                        title="{{ __('Page preview') }}"
                        class="h-[min(70vh,720px)] w-full border-0 bg-white"
                        loading="lazy"
                    ></iframe>
                </section>
            @elseif ($editingId === null)
                <p class="mom-subtext rounded-mom-chrome border border-[var(--border-panel-soft)] px-4 py-3">{{ __('Save the page once to enable the production preview panel.') }}</p>
            @endif

            @if ($hasSectionTokens && ($sectionLibraryDeprecated ?? false))
                <div class="rounded-mom-chrome border border-[rgba(197,160,89,0.35)] bg-[rgba(197,160,89,0.08)] px-4 py-3 text-sm text-[var(--text-secondary)]">
                    {{ config('platform_composition.section_library_deprecation_note') }}
                </div>
            @endif

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Basic') }}</h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Page title') }}</label>
                        <input type="text" wire:model.live="title" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]" />
                        @error('title') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Slug') }}</label>
                        <input type="text" wire:model="slug" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-sm text-[var(--text-primary)]" />
                        @error('slug') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2 flex flex-wrap items-end gap-6">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" wire:model="is_active" id="page-active" class="rounded border-[rgba(255,255,255,0.15)]" />
                            <label for="page-active" class="text-sm text-[var(--text-secondary)]">{{ __('Live') }}</label>
                        </div>
                        <div>
                            <label for="page-layout-mode" class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Page layout') }}</label>
                            <select wire:model="layout_mode" id="page-layout-mode" class="mt-2 rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]">
                                @foreach (\App\Enums\PageLayoutMode::cases() as $mode)
                                    <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                                @endforeach
                            </select>
                            <p class="mom-subtext mt-1">{{ __('Full width (canvas) removes the default max-width shell so your blocks control structure edge-to-edge.') }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-2">{{ __('Page sections') }}</h3>
                <p class="mom-subtext mb-4 max-w-3xl">{{ __('Each line is one section on the public page (hero, services, contact form, etc.). Drag order with Up/Down. To change headlines and button text, use Edit content — not the code fields below.') }}</p>

                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="addSection" class="rounded-mom-chrome border border-[rgba(197,160,89,0.35)] bg-[rgba(197,160,89,0.08)] px-4 py-2 text-sm font-semibold text-mom-gold hover:bg-[rgba(197,160,89,0.14)]">
                        {{ __('Add section') }}
                    </button>
                    @if ($canUseDeveloperBlockTools ?? false)
                        <button type="button" wire:click="openDeveloperBlockModal" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-2 text-sm text-[var(--text-secondary)] hover:bg-[var(--bg-hover)]">
                            {{ __('Developer tools') }}
                        </button>
                    @endif
                    <div class="flex flex-wrap items-center gap-2">
                        <select wire:model.live="module_choice" class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]">
                            <option value="">{{ __('Insert module…') }}</option>
                            @foreach ($moduleOptions as $option)
                                <option value="{{ $option['key'] }}">
                                    {{ $option['label'] }}
                                    @if ($option['source'] === 'dynamic')
                                        ({{ __('Custom') }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <button type="button" wire:click="appendModule" wire:loading.attr="disabled" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)] disabled:opacity-50">{{ __('Add module line') }}</button>
                    </div>
                    @if (count($moduleOptions) === 0)
                        <p class="mt-2 text-xs text-[var(--text-muted)]">{{ __('No modules yet. Create one in Module Builder or register Livewire modules in config/modules.php.') }}</p>
                    @else
                        <p class="mt-2 text-xs text-[var(--text-muted)]">{{ __('Pick a module from the list, then click Add module line.') }}</p>
                    @endif
                    @error('module_choice') <span class="mt-2 block text-xs text-[var(--danger)]">{{ $message }}</span> @enderror
                </div>

                <ul
                    class="mt-6 space-y-2"
                    data-sortable-list
                    data-sortable-method="syncContentPartsOrder"
                    data-sortable-item="[data-sortable-item]"
                    data-sortable-key="data-sortable-item"
                    data-sortable-handle="[data-sortable-handle]"
                >
                    @foreach ($contentParts as $idx => $part)
                        <li
                            wire:key="part-{{ $idx }}-{{ $part['type'] }}-{{ $part['slug'] }}"
                            data-sortable-item="{{ $idx }}"
                            class="flex flex-wrap items-center justify-between gap-2 rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] px-3 py-2 text-sm text-[var(--text-primary)]"
                        >
                            <span class="flex items-center gap-2 font-medium">
                                <span
                                    role="button"
                                    tabindex="0"
                                    data-sortable-handle
                                    class="cursor-grab select-none px-1 text-[var(--text-muted)] active:cursor-grabbing"
                                    aria-label="{{ __('Drag to reorder') }}"
                                >⋮⋮</span>
                                @if ($part['type'] === 'block')
                                    {{ app(\App\Services\SiteArchitect\PageSectionCatalog::class)->displayNameForSlug($part['slug']) }}
                                @else
                                    {{ ucfirst($part['type']) }} · {{ $part['slug'] }}
                                @endif
                                @if ($canUseDeveloperBlockTools ?? false)
                                    <span class="mt-0.5 block font-mono text-[10px] text-[var(--text-muted)]">{{ '{' . '{' . $part['type'] . ':' . $part['slug'] . '}' . '}' }}</span>
                                @endif
                            </span>
                            <span class="flex flex-wrap gap-1 text-xs">
                                @if ($part['type'] === 'block')
                                    <a href="{{ route('site-architect.block-studio.index', ['block' => $part['slug']]) }}" class="text-mom-gold hover:underline" target="_blank" rel="noopener">{{ __('Edit section content') }}</a>
                                @endif
                                <button type="button" wire:click="movePartUp({{ $idx }})" class="hover:text-[var(--text-primary)]">{{ __('Up') }}</button>
                                <button type="button" wire:click="movePartDown({{ $idx }})" class="hover:text-[var(--text-primary)]">{{ __('Down') }}</button>
                                <button type="button" wire:click="removePart({{ $idx }})" class="text-[var(--danger)] hover:underline">{{ __('Remove') }}</button>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>

            @php
                $hideGeneratedSeo = \App\Services\Seo\SeoOwnershipGuard::hideSeoEditingOnGeneratedPages()
                    && ($page_source ?? '') === 'generated';
            @endphp
            @if ($hideGeneratedSeo)
                <section class="mom-card p-6">
                    <h3 class="mom-section-title mb-2">{{ __('SEO') }}</h3>
                    <p class="mom-subtext">{{ __('This page is auto-generated. SEO is resolved at runtime from service, country, and SEO rules — edit the Operations service master or country data instead.') }}</p>
                </section>
            @else
            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('SEO') }}</h3>

                @if (($hijackStrategiesForPage ?? []) !== [])
                    <div class="mb-6 rounded-mom-chrome border border-mom-gold/30 bg-[rgba(212,175,55,0.06)] p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-mom-gold">{{ __('Growth Center — Hijack strategies') }}</p>
                        <p class="mom-subtext mt-2">{{ __('Gemini-generated counter-SEO from competitor gaps. Apply to pre-fill meta tags and H1 for this page.') }}</p>
                        <ul class="mt-4 space-y-3">
                            @foreach ($hijackStrategiesForPage as $entry)
                                @php
                                    $strategy = $entry['strategy'] ?? [];
                                @endphp
                                <li class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] p-3">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-medium text-[var(--text-primary)]">{{ $strategy['keyword'] ?? '—' }}</p>
                                            <p class="mom-subtext mt-1">
                                                {{ __('Priority :p · vs :competitor · gap :gap', [
                                                    'p' => $strategy['hijack_priority'] ?? '—',
                                                    'competitor' => $strategy['competitor_name'] ?? __('competitor'),
                                                    'gap' => $strategy['position_gap'] ?? '—',
                                                ]) }}
                                            </p>
                                            @php
                                                $previewTitle = $strategy['autonomous_content']['meta_title']
                                                    ?? $strategy['meta_title']
                                                    ?? null;
                                            @endphp
                                            @if (! empty($previewTitle))
                                                <p class="mt-1 text-xs text-[var(--success)]">
                                                    <span class="font-semibold">{{ ! empty($strategy['autonomous_content']['meta_title']) ? __('AI optimized') : __('Suggested title') }}:</span>
                                                    {{ $previewTitle }}
                                                </p>
                                            @endif
                                        </div>
                                        <div class="flex shrink-0 flex-col gap-2">
                                        <button
                                            type="button"
                                            wire:click="applyHijackStrategy('{{ $entry['key'] }}')"
                                            wire:loading.attr="disabled"
                                            class="mom-cta-primary mom-cta-compact"
                                        >{{ __('Apply to page') }}</button>
                                        @if ($editingId)
                                            <button
                                                type="button"
                                                wire:click="applyAndPublishHijackStrategy('{{ $entry['key'] }}')"
                                                wire:loading.attr="disabled"
                                                wire:confirm="{{ __('Publish AI SEO to this page and sync seo_entities now?') }}"
                                                class="rounded-mom-chrome border border-[rgba(98,195,112,0.45)] bg-[rgba(98,195,112,0.12)] px-3 py-2 text-[11px] font-semibold text-[var(--success)]"
                                            >{{ __('One-click publish') }}</button>
                                        @endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        @error('hijack_strategy') <p class="mt-2 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Meta title') }}</label>
                        <input type="text" wire:model="meta_title" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Meta description') }}</label>
                        <textarea wire:model="meta_description" rows="3" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Focus keywords (up to 10)') }}</p>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach (range(0, 9) as $i)
                                <input type="text" wire:model="focusKeywords.{{ $i }}" class="w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" placeholder="{{ __('Keyword') }} {{ $i + 1 }}" />
                            @endforeach
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Legacy keywords (comma-separated, optional)') }}</label>
                        <input type="text" wire:model="keywords" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                    </div>
                    @foreach (['h1' => __('H1'), 'h4' => __('H4'), 'h5' => __('H5'), 'h6' => __('H6')] as $field => $label)
                        <div>
                            <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ $label }}</label>
                            <input type="text" wire:model="{{ $field }}" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                        </div>
                    @endforeach
                    <div class="md:col-span-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('H2 headings (repeatable)') }}</p>
                        @foreach (range(0, 7) as $i)
                            <input type="text" wire:model="headingH2.{{ $i }}" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                        @endforeach
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('H3 headings (repeatable)') }}</p>
                        @foreach (range(0, 7) as $i)
                            <input type="text" wire:model="headingH3.{{ $i }}" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Technical & canonical') }}</h3>
                <p class="mom-subtext mb-4 max-w-2xl">{{ __('Override crawl directives for this URL only. Leave robots blank to inherit Growth Center global settings.') }}</p>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Canonical URL') }}</label>
                        <input type="text" wire:model="canonical_url" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" placeholder="https://example.com/p/your-page" />
                        @error('canonical_url') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Robots meta') }}</label>
                        <select wire:model="robots_meta" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]">
                            <option value="">{{ __('Inherit global (Growth Center)') }}</option>
                            <option value="index, follow">index, follow</option>
                            <option value="noindex, nofollow">noindex, nofollow</option>
                            <option value="noindex, follow">noindex, follow</option>
                            <option value="index, nofollow">index, nofollow</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Social & sharing (Open Graph)') }}</h3>
                <p class="mom-subtext mb-4 max-w-2xl">{{ __('Optional image for this page in link previews. Use a full URL or a path under storage (e.g. after uploading in Media).') }}</p>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('OG / social image') }}</label>
                        <input type="text" wire:model="og_image" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" placeholder="https://… or media/…" />
                        @error('og_image') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('OG image alt text') }}</label>
                        <input type="text" wire:model="og_image_alt" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                        @error('og_image_alt') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Images & alt text') }}</h3>
                <p class="mom-subtext max-w-2xl">{{ __('Set alt text on <img> tags inside block HTML, and use the OG image alt field above for the share card. The Media library can store assets; reference them in blocks with proper alt attributes for SEO and accessibility.') }}</p>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('AEO & FAQs') }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Featured question (snippet)') }}</label>
                        <textarea wire:model="aeo_question" rows="2" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Featured answer') }}</label>
                        <textarea wire:model="aeo_answer" rows="4" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm"></textarea>
                    </div>
                    @foreach ($faqRows as $idx => $row)
                        <div wire:key="page-faq-{{ $idx }}" class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] p-4">
                            <p class="mom-micro mb-2">{{ __('FAQ') }} #{{ $idx + 1 }}</p>
                            <textarea wire:model="faqRows.{{ $idx }}.question" rows="2" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" placeholder="{{ __('Question') }}"></textarea>
                            <textarea wire:model="faqRows.{{ $idx }}.answer" rows="4" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" placeholder="{{ __('Answer') }}"></textarea>
                        </div>
                    @endforeach
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('AI context') }}</label>
                        <textarea wire:model="ai_context" rows="5" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Search intent') }}</label>
                        <input type="text" wire:model="search_intent" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                    </div>
                </div>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Performance & language') }}</h3>
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="md:col-span-2 rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Readability (draft signal)') }}</p>
                        @if ($readabilityHint)
                            <p class="mt-2 text-sm text-[var(--text-primary)]">{{ __('Score') }}: {{ $readabilityHint['score'] }}/100 @if ($readabilityHint['avg_words_per_sentence'] !== null) · {{ __('Avg words / sentence') }}: {{ $readabilityHint['avg_words_per_sentence'] }} @endif</p>
                            <p class="mom-subtext mt-1">{{ $readabilityHint['note'] }}</p>
                        @endif
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Internal linking ideas') }}</p>
                        <p class="mom-subtext mt-1 mb-2">{{ __('Copy paths into blocks as internal links to strengthen topical clusters.') }}</p>
                        <ul class="mom-subtext max-h-36 list-inside list-disc overflow-y-auto custom-scrollbar rounded-mom-chrome border border-[var(--border-panel-soft)] p-3">
                            @forelse ($otherPagesForLinks as $op)
                                <li wire:key="link-hint-{{ $op->id }}">
                                    <span class="text-[var(--text-primary)]">{{ $op->title }}</span>
                                    — <code class="font-mono text-xs">{{ '/p/'.$op->slug }}</code>
                                </li>
                            @empty
                                <li>{{ __('No other pages yet — create another page to see suggestions.') }}</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="md:col-span-2 flex flex-wrap items-center gap-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Content freshness') }}</p>
                            <p class="mom-subtext mt-1">{{ __('Last marked reviewed') }}: {{ $content_reviewed_label !== '' ? $content_reviewed_label : __('—') }}</p>
                        </div>
                        @if ($editingId)
                            <button type="button" wire:click="markContentReviewed" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)]">{{ __('Mark reviewed now') }}</button>
                        @endif
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Hreflang JSON') }}</label>
                        <p class="mom-subtext mb-2">{{ __('Locale keys map to absolute URLs (e.g. "en": "https://…", "hi": "https://…").') }}</p>
                        <textarea wire:model="hreflang_json_input" rows="5" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs"></textarea>
                        @error('hreflang_json_input') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
            @endif

            @if (! $hideGeneratedSeo)
            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('On-page SEO checklist') }}</h3>
                <p class="mom-subtext mb-4">{{ __('Meta length, canonical, OG social layer, and keyword alignment — heuristic signals similar to Rank Math-style guidance.') }}</p>
                @if ($onPageSeo)
                    <p class="text-lg font-semibold text-[var(--text-primary)]">{{ $onPageSeo['score'] }}/100</p>
                    @if (count($onPageSeo['checks']) > 0)
                        <ul class="mom-subtext mt-2 list-inside list-disc text-[var(--text-secondary)]">
                            @foreach ($onPageSeo['checks'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if (count($onPageSeo['warnings']) > 0)
                        <ul class="mom-subtext mt-2 list-inside list-disc text-[var(--warning)]">
                            @foreach ($onPageSeo['warnings'] as $warn)
                                <li>{{ $warn }}</li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </section>
            @endif

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('AEO+ / LLM readiness') }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Entity tags') }}</label>
                        <p class="mom-subtext mb-2">{{ __('Comma-separated entities this page should reinforce for AI systems.') }}</p>
                        <textarea wire:model="entity_tags_input" rows="2" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" placeholder="{{ __('e.g. Home consulting, India, Post-operative care') }}"></textarea>
                    </div>
                    <label class="flex cursor-pointer items-center gap-3 text-sm text-[var(--text-secondary)]">
                        <input type="checkbox" wire:model.live="fact_check_verified" class="rounded border-[rgba(255,255,255,0.15)]" />
                        {{ __('Fact-check verified (editor attestation)') }}
                    </label>
                    @if ($llmReadiness)
                        <div class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('LLM readiness (heuristic)') }}</p>
                            <p class="mt-2 text-lg font-semibold text-[var(--text-primary)]">{{ $llmReadiness['score'] }}/100</p>
                            @if (count($llmReadiness['checks']) > 0)
                                <ul class="mom-subtext mt-2 list-inside list-disc">
                                    @foreach ($llmReadiness['checks'] as $check)
                                        <li>{{ $check }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mom-subtext mt-2">{{ __('Fill SEO, AEO, and schema fields to raise this score.') }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </section>

            <section class="mom-card p-6">
                <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="mom-section-title">{{ __('GEO (Countries & States)') }}</h3>
                        <p class="mom-subtext mt-1">{{ __('Select coverage countries and states; country and region details are read-only from the directory.') }}</p>
                    </div>
                    <p class="mom-micro text-[var(--text-muted)]">
                        {{ count($selectedPinIds) }} {{ __('selected') }}
                        · {{ $pinCodes->count() }} {{ __('total') }}
                    </p>
                </div>
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="pinCodeFilter"
                        class="min-w-[12rem] flex-1 rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]"
                        placeholder="{{ __('Filter by country, area, city…') }}"
                        autocomplete="off"
                    />
                    <button type="button" wire:click="selectAllPinCodes" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Select all') }}</button>
                    @if (trim($pinCodeFilter) !== '')
                        <button type="button" wire:click="selectFilteredPinCodes" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Select filtered') }}</button>
                    @endif
                    <button type="button" wire:click="clearAllPinCodes" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Clear all') }}</button>
                </div>
                <div class="custom-scrollbar max-h-64 overflow-y-auto rounded-mom-chrome border border-[var(--border-panel-soft)] p-3">
                    @forelse ($visiblePinCodes as $pc)
                        <label wire:key="pin-{{ $pc->id }}" class="flex cursor-pointer gap-3 border-b border-[color:var(--border-tabstrip-divider)] py-2 last:border-0">
                            <input type="checkbox" wire:model.live="selectedPinIds" value="{{ $pc->id }}" class="mt-1 rounded border-[rgba(255,255,255,0.15)]" />
                            <span class="text-sm">
                                <span class="font-mono text-[var(--text-primary)]">{{ $pc->pincode }}</span>
                                — {{ $pc->area_name }}, {{ $pc->city ?? '—' }}
                            </span>
                        </label>
                    @empty
                        <p class="mom-subtext text-sm">{{ __('No pincodes match your filter.') }}</p>
                    @endforelse
                </div>

                @foreach ($selectedPinIds as $pid)
                    @php $pinRow = $pinCodes->firstWhere('id', (int) $pid); @endphp
                    @if ($pinRow)
                        <div class="mom-card mt-4 border border-[var(--border-panel-soft)] p-4" wire:key="geo-{{ $pid }}">
                            <p class="font-mono text-sm text-[var(--text-primary)]">{{ $pinRow->pincode }}</p>
                            <p class="mom-subtext mt-1">{{ __('Area') }}: {{ $pinRow->area_name }} · {{ __('City') }}: {{ $pinRow->city ?? '—' }}</p>
                            <p class="mom-micro mt-2">{{ __('Suggested phrases') }}</p>
                            <ul class="mom-subtext mt-1 list-inside list-disc">
                                @foreach (\App\Livewire\SiteArchitect\Pages::defaultKeywordHints($pinRow) as $hint)
                                    <li>{{ $hint }}</li>
                                @endforeach
                            </ul>
                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                <label class="flex items-center gap-2 text-sm text-[var(--text-secondary)]">
                                    <input type="checkbox" wire:model="pinPivot.{{ $pid }}.serviceability" class="rounded border-[rgba(255,255,255,0.15)]" />
                                    {{ __('Serviceability') }}
                                </label>
                                <div>
                                    <label class="block text-xs text-[var(--text-muted)]">{{ __('Delivery charge') }}</label>
                                    <input type="text" wire:model="pinPivot.{{ $pid }}.delivery_charge" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-2 py-1 text-sm" placeholder="0.00" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs text-[var(--text-muted)]">{{ __('Location keywords override') }}</label>
                                    <textarea wire:model="pinPivot.{{ $pid }}.location_keywords" rows="2" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-2 py-1 text-sm" placeholder="{{ __('Optional; overrides auto-generated GEO phrases') }}"></textarea>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </section>

            @if ($editingId)
                <section class="mom-card p-6">
                    <h3 class="mom-section-title mb-4">{{ __('Revision history') }}</h3>
                    <p class="mom-subtext mb-4">{{ __('Snapshots are stored when you save (latest 40 kept). Restore loads values into this form — save to publish.') }}</p>
                    <div class="custom-scrollbar max-h-72 space-y-2 overflow-y-auto rounded-mom-chrome border border-[var(--border-panel-soft)] p-3">
                        @forelse ($revisions as $rev)
                            <div wire:key="rev-{{ $rev->id }}" class="flex flex-wrap items-center justify-between gap-2 border-b border-[color:var(--border-tabstrip-divider)] py-2 text-sm last:border-0">
                                <span class="text-[var(--text-secondary)]">
                                    {{ $rev->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                    @if ($rev->user)
                                        · {{ $rev->user->name }}
                                    @endif
                                </span>
                                <button type="button" wire:click="restoreRevision({{ $rev->id }})" class="text-mom-gold hover:underline">{{ __('Restore into form') }}</button>
                            </div>
                        @empty
                            <p class="mom-subtext">{{ __('No snapshots yet — save the page once to create the first revision.') }}</p>
                        @endforelse
                    </div>
                </section>
            @endif

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Schema & tracking') }}</h3>
                <p class="mom-subtext mb-4">{{ __('JSON-LD below is emitted on the public page in addition to global organization schema.') }}</p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Schema type') }}</label>
                        <input type="text" wire:model="schema_type" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" placeholder="MedicalBusiness, Service, FAQPage, …" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Schema JSON') }}</label>
                        <textarea wire:model="schema_json_input" rows="8" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs"></textarea>
                        @error('schema_json_input') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('GTM code') }}</label>
                        <textarea wire:model="gtm_code" rows="3" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Pixel code') }}</label>
                        <textarea wire:model="pixel_code" rows="3" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs"></textarea>
                    </div>
                </div>
            </section>

            @include('livewire.site-architect.partials.architect-save-approvals')

            <div class="flex flex-wrap gap-3">
                <button type="button" wire:click="savePage" class="rounded-mom-chrome bg-[var(--accent-gold)] px-5 py-2.5 text-sm font-semibold text-[#120f0d]">{{ __('Save page') }}</button>
                <button type="button" wire:click="cancelForm" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-5 py-2.5 text-sm text-[var(--text-secondary)]">{{ __('Cancel') }}</button>
            </div>
        </div>
    @endif

    @if ($sectionPickerOpen)
        @include('livewire.site-architect.partials.section-picker-modal', [
            'sectionPickerGroups' => $sectionPickerGroups,
            'sectionPickerCategories' => $sectionPickerCategories,
            'canUseDeveloperBlockTools' => $canUseDeveloperBlockTools,
        ])
    @endif

    @if ($blockModalOpen)
        <div class="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 p-4" wire:click.self="closeBlockModal">
            <div class="mom-card max-h-[90vh] w-full max-w-2xl overflow-y-auto p-6" @click.stop>
                <h4 class="mom-section-title">{{ __('Add section to this page') }}</h4>
                <p class="mom-subtext mt-2 max-w-xl text-sm">{{ __('Pick an existing block slug (recommended). Marketing copy is edited in Blocks Studio. Use Blocks Factory only when a developer needs new HTML/Blade.') }}</p>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Block name') }}</label>
                        <input type="text" wire:model.live="block_name" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                        @error('block_name') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Block slug') }}</label>
                        <input
                            type="text"
                            wire:model="block_slug"
                            class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-sm"
                            @if ($blockEditingSlug !== null) disabled @endif
                        />
                        @error('block_slug') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    @include('livewire.site-architect.partials.service-insert-controls', [
                        'services' => $servicesForInsert,
                        'serviceCatalogNonce' => $serviceCatalogNonce,
                        'showManageLink' => true,
                    ])
                    @include('livewire.site-architect.partials.module-insert-controls', [
                        'moduleOptions' => $moduleOptions,
                        'wireModel' => 'block_module_choice',
                        'appendAction' => 'appendModuleTokenToBlock',
                    ])
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Developer code (optional)') }}</label>
                        <p class="mom-subtext mt-1 text-xs">{{ __('Leave empty for managed blocks. If you see a “use Blocks Studio” error, open Blocks Studio instead of pasting code here.') }}</p>
                        <textarea wire:model="block_code" rows="10" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs" placeholder="@include('blocks.home.hero-home')"></textarea>
                        @error('block_code') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    @include('livewire.site-architect.partials.block-custom-css-field', ['wireModel' => 'block_custom_css'])
                </div>
                <div class="mt-6 flex gap-2">
                    <button type="button" wire:click="saveBlockInModal" class="rounded-mom-chrome bg-[var(--accent-gold)] px-4 py-2 text-sm font-semibold text-[#120f0d]">{{ __('Save block') }}</button>
                    <button type="button" wire:click="closeBlockModal" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-4 py-2 text-sm">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    @endif

    <x-bulk.action-modal :open="$bulkModalOpen" :preview="$bulkGovernancePreview" />
</div>

@push('head')
    <style>
        .sortable-ghost { opacity: 0.45; background: rgba(197,160,89,0.12); }
        .sortable-chosen { box-shadow: 0 0 0 2px rgba(197,160,89,0.35); }
        .sortable-drag { cursor: grabbing !important; }
    </style>
@endpush
