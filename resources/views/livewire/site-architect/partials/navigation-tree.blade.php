@php
    /** @var list<array<string, mixed>> $nodes */
    /** @var string $zone */
    $pathPrefix = $pathPrefix ?? [];
@endphp

<ul
    class="space-y-2 {{ ($depth ?? 0) > 0 ? 'ml-4 border-l border-[var(--border-panel-soft)] pl-3' : '' }}"
    data-nav-sortable-list
    data-nav-zone="{{ $zone }}"
    data-nav-parent-path='@json($pathPrefix)'
>
    @forelse ($nodes as $index => $node)
        @php
            $path = array_merge($pathPrefix, [$index]);
            $children = is_array($node['children'] ?? null) ? $node['children'] : [];
            $isAutoSynced = (bool) ($node['auto_synced'] ?? false);
            $isCatalogAttachment = (bool) ($node['catalog_attachment'] ?? false);
            $type = (string) ($node['item_type'] ?? 'page');
            if ($isAutoSynced) {
                $type = strtoupper(str_replace('_', ' ', $type !== 'page' ? $type : (explode(':', (string) ($node['catalog_key'] ?? 'catalog'))[0] ?? 'catalog')));
            } else {
                $type = strtoupper(str_replace('_', ' ', $type));
            }
            $label = (string) ($node['label'] ?? __('Item'));
            $nodeKey = $isAutoSynced && filled($node['catalog_key'] ?? null)
                ? 'auto:'.($node['catalog_key'])
                : (filled($node['_attachment_id'] ?? null)
                    ? 'manual:'.($node['_attachment_id'])
                    : (filled($node['id'] ?? null) ? 'manual:'.($node['id']) : 'manual:idx:'.$index));
            $parentCatalogKey = $isAutoSynced ? (string) ($node['catalog_key'] ?? '') : null;
            $editUrl = null;
            if ($isAutoSynced && filled($node['catalog_key'] ?? null)) {
                $catalogKey = (string) $node['catalog_key'];
                if (str_starts_with($catalogKey, 'category:')) {
                    $editUrl = route('operations.service-categories.edit', (int) substr($catalogKey, 9));
                } elseif (str_starts_with($catalogKey, 'service:')) {
                    $editUrl = route('operations.services.edit', (int) substr($catalogKey, 8));
                } elseif (str_starts_with($catalogKey, 'sub_service:') && filled($node['service_id'] ?? null)) {
                    $editUrl = route('operations.services.sub-services.edit', [(int) $node['service_id'], (int) substr($catalogKey, 12)]);
                }
            }
        @endphp
        <li
            wire:key="nav-{{ $zone }}-{{ implode('-', $path) }}-{{ $nodeKey }}"
            data-nav-node-key="{{ $nodeKey }}"
            class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] p-3"
        >
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="flex min-w-0 items-start gap-2">
                    <span
                        role="button"
                        tabindex="0"
                        data-sortable-handle
                        class="mt-0.5 cursor-grab select-none px-1 text-[var(--text-muted)] active:cursor-grabbing"
                        aria-label="{{ __('Drag to reorder') }}"
                        title="{{ __('Drag to reorder') }}"
                    >⋮⋮</span>
                    <div class="min-w-0">
                        <p class="font-medium text-[var(--text-primary)]">{{ $label }}</p>
                        <p class="mom-micro text-[var(--text-muted)]">
                            {{ $type }}
                            @if ($isAutoSynced)
                                <span class="ml-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-emerald-400">
                                    {{ __('Auto from catalog') }}
                                </span>
                            @elseif ($isCatalogAttachment)
                                <span class="ml-2 text-[var(--text-muted)]">{{ __('· Manual under catalog') }}</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-1">
                    @if ($editUrl)
                        <a
                            href="{{ $editUrl }}"
                            target="_blank"
                            rel="noopener"
                            class="mom-cta-compact mom-cta-ghost inline-flex h-8 w-8 items-center justify-center p-0"
                            title="{{ __('Edit in Operations') }}"
                            aria-label="{{ __('Edit in Operations') }}"
                        >
                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                        </a>
                    @elseif (! $isAutoSynced)
                        <button
                            type="button"
                            wire:click="queueEditItem(@js($zone), @js($path))"
                            class="mom-cta-compact mom-cta-ghost inline-flex h-8 w-8 items-center justify-center p-0"
                            title="{{ __('Edit') }}"
                            aria-label="{{ __('Edit') }}"
                        >
                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                        </button>
                    @endif
                    <button
                        type="button"
                        wire:click="queueAddChild(@js($path), @js($zone), @js($parentCatalogKey))"
                        class="mom-cta-compact mom-cta-ghost inline-flex items-center gap-1 text-xs"
                        title="{{ __('Add submenu') }}"
                    >
                        <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                        <span class="hidden sm:inline">{{ __('Submenu') }}</span>
                    </button>
                    <button
                        type="button"
                        wire:click="removeMenuItem(@js($zone), @js($path))"
                        wire:confirm="{{ $isAutoSynced ? __('Hide this catalog item from the menu? It will reappear if you remove it from exclusions later.') : __('Remove this item and its submenus?') }}"
                        class="mom-cta-compact inline-flex h-8 w-8 items-center justify-center p-0 text-[var(--danger)]"
                        title="{{ __('Remove') }}"
                        aria-label="{{ __('Remove') }}"
                    >
                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                    </button>
                </div>
            </div>
            @if ($children !== [])
                @include('livewire.site-architect.partials.navigation-tree', [
                    'nodes' => $children,
                    'zone' => $zone,
                    'depth' => ($depth ?? 0) + 1,
                    'pathPrefix' => $path,
                ])
            @endif
        </li>
    @empty
        @if (($depth ?? 0) === 0)
            <li class="mom-subtext text-sm">{{ __('No items yet. Add a menu item below.') }}</li>
        @endif
    @endforelse
</ul>
