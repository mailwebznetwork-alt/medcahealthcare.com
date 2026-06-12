<div id="site-navigation-root" class="space-y-6" wire:key="site-navigation-root">
    <div class="mom-card p-6">
        <h2 class="text-lg font-semibold text-[var(--text-primary)]">{{ __('Navigation menus') }}</h2>
        <p class="mom-subtext mt-2 max-w-3xl">
            {{ __('Manual header and footer menus for pages, groups, and custom URLs. The Services dropdown is auto-built from your live catalog (categories → services) and updates when you add, edit, or remove catalog items.') }}
        </p>
        @if ($lastSavedAt)
            <p class="mom-micro mt-3 text-[var(--text-muted)]">{{ __('Last saved: :time', ['time' => $lastSavedAt]) }}</p>
        @endif
    </div>

    <div class="mom-card p-6 space-y-4">
        <h3 class="mom-section-title">{{ __('Add menu item') }}</h3>
        @if ($editPath !== null)
            <p class="text-sm text-mom-gold">
                {{ __('Editing menu item') }}
                <button type="button" wire:click="cancelEdit" class="ml-2 underline">{{ __('Cancel edit') }}</button>
            </p>
        @elseif ($addParentPath !== [] || filled($addParentCatalogKey))
            <p class="text-sm text-mom-gold">
                @if (filled($addParentCatalogKey))
                    {{ __('Adding manual submenu under catalog item :key', ['key' => $addParentCatalogKey]) }}
                @else
                    {{ __('Adding submenu under level :depth', ['depth' => count($addParentPath) + 1]) }}
                @endif
                <button type="button" wire:click="clearAddTarget" class="ml-2 underline">{{ __('Cancel target') }}</button>
            </p>
        @endif
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="mom-micro text-[var(--text-muted)]">{{ __('Menu zone') }}</label>
                <select wire:model.live="addZone" class="mom-input mt-1 block w-full text-sm">
                    <option value="header">{{ __('Header') }}</option>
                    <option value="footer">{{ __('Footer') }}</option>
                </select>
            </div>
            <div>
                <label class="mom-micro text-[var(--text-muted)]">{{ __('Link type') }}</label>
                <select wire:model.live="addType" class="mom-input mt-1 block w-full text-sm">
                    <option value="group">{{ __('Group label (dropdown parent)') }}</option>
                    <option value="page">{{ __('CMS page') }}</option>
                    <option value="category">{{ __('Service category') }}</option>
                    <option value="service">{{ __('Service') }}</option>
                    <option value="sub_service">{{ __('Sub-service') }}</option>
                    <option value="url">{{ __('Custom URL') }}</option>
                </select>
            </div>
            <div>
                <label class="mom-micro text-[var(--text-muted)]">{{ __('Label override (optional)') }}</label>
                <input type="text" wire:model="addTitle" class="mom-input mt-1 block w-full text-sm" placeholder="{{ __('Public menu label') }}" />
                @error('addTitle') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mom-micro text-[var(--text-muted)]">{{ __('Search catalog / pages') }}</label>
                <input type="search" wire:model.live.debounce.300ms="poolSearch" class="mom-input mt-1 block w-full text-sm" placeholder="{{ __('Type 2+ characters…') }}" />
                <p class="mom-micro mt-1">{{ __(':count live pages in site', ['count' => number_format($livePageCount)]) }}</p>
            </div>

            @if ($addType === 'page')
                <div>
                    <label class="mom-micro text-[var(--text-muted)]">{{ __('Page') }}</label>
                    <select wire:model="addPageId" class="mom-input mt-1 block w-full text-sm">
                        <option value="">{{ __('Select page…') }}</option>
                        @foreach ($poolPages as $page)
                            <option value="{{ $page->id }}">{{ $page->title }} ({{ $page->slug }})</option>
                        @endforeach
                    </select>
                    @error('addPageId') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                </div>
            @elseif ($addType === 'category')
                <div>
                    <label class="mom-micro text-[var(--text-muted)]">{{ __('Category') }}</label>
                    <select wire:model="addCategoryId" class="mom-input mt-1 block w-full text-sm">
                        <option value="">{{ __('Select category…') }}</option>
                        @foreach ($categoryOptions as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }} ({{ $cat->code }})</option>
                        @endforeach
                    </select>
                    @error('addCategoryId') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                </div>
            @elseif ($addType === 'service')
                <div>
                    <label class="mom-micro text-[var(--text-muted)]">{{ __('Service') }}</label>
                    <select wire:model="addServiceId" class="mom-input mt-1 block w-full text-sm">
                        <option value="">{{ __('Select service…') }}</option>
                        @foreach ($serviceOptions as $svc)
                            <option value="{{ $svc->id }}">{{ $svc->title }} ({{ $svc->service_code }})</option>
                        @endforeach
                    </select>
                    @error('addServiceId') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                </div>
            @elseif ($addType === 'sub_service')
                <div>
                    <label class="mom-micro text-[var(--text-muted)]">{{ __('Sub-service') }}</label>
                    <select wire:model="addSubServiceId" class="mom-input mt-1 block w-full text-sm">
                        <option value="">{{ __('Select sub-service…') }}</option>
                        @foreach ($subServiceOptions as $sub)
                            <option value="{{ $sub->id }}">{{ $sub->title }} — {{ $sub->service?->service_code }}/{{ $sub->sub_service_code }}</option>
                        @endforeach
                    </select>
                    @error('addSubServiceId') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                </div>
            @elseif ($addType === 'url')
                <div>
                    <label class="mom-micro text-[var(--text-muted)]">{{ __('URL') }}</label>
                    <input type="url" wire:model="addCustomUrl" class="mom-input mt-1 block w-full text-sm" placeholder="https://…" />
                    @error('addCustomUrl') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                </div>
            @endif
        </div>

        <button type="button" wire:click="addMenuItem" class="mom-cta-primary">
            {{ $editPath !== null ? __('Save changes') : __('Add to menu') }}
        </button>
    </div>

    <div class="flex w-full flex-col gap-6">
        <div class="mom-card w-full p-6">
            <h3 class="mom-section-title mb-3">{{ __('Header menu tree') }}</h3>
            @include('livewire.site-architect.partials.navigation-tree', ['nodes' => $headerTree, 'zone' => 'header', 'depth' => 0, 'pathPrefix' => []])
        </div>
        <div class="mom-card w-full p-6">
            <h3 class="mom-section-title mb-3">{{ __('Footer menu tree') }}</h3>
            @include('livewire.site-architect.partials.navigation-tree', ['nodes' => $footerTree, 'zone' => 'footer', 'depth' => 0, 'pathPrefix' => []])
        </div>
    </div>
</div>
