<div
    x-data
    x-on:block-context-copied.window="
        if ($event.detail?.text) {
            navigator.clipboard.writeText($event.detail.text).catch(() => {});
        }
    "
>
    @if (session('status'))
        <div class="mom-card mb-6 border border-[rgba(98,195,120,0.28)] bg-[rgba(98,195,120,0.08)] px-4 py-3 text-sm text-[var(--success)]" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mom-card mb-6 border border-[rgba(226,92,92,0.28)] bg-[rgba(226,92,92,0.08)] px-4 py-3 text-sm text-[var(--danger)]" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if ($mode === 'list')
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-[var(--text-primary)]">{{ __('Blocks Factory') }}</h2>
            <a
                href="{{ route('site-architect.block-factory.index') }}?create=1"
                wire:click.prevent="startCreate"
                role="button"
                class="inline-flex cursor-pointer rounded-mom-chrome border border-[rgba(197,160,89,0.28)] bg-[rgba(197,160,89,0.1)] px-4 py-2 text-sm font-medium text-mom-gold no-underline"
            >
                {{ __('Create block') }}
            </a>
        </div>

        <div class="mb-6 max-w-xl">
            <label for="block-factory-search" class="mom-micro text-[var(--text-muted)]">{{ __('Search blocks') }}</label>
            <input
                id="block-factory-search"
                type="search"
                wire:model.live.debounce.400ms="search"
                class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]"
                placeholder="{{ __('Name, slug, type, or description…') }}"
                autocomplete="off"
            />
        </div>

        <x-bulk.selection-toolbar :count="$this->bulkSelectedCount()" :actions="['modify', 'duplicate', 'delete', 'publish', 'unpublish', 'export', 'sync']" />

        <div class="mom-card overflow-x-auto p-0">
            <table class="mom-table w-full min-w-[960px] text-left text-sm">
                <thead>
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" class="rounded border-[var(--border-panel-soft)]" wire:click="selectAllVisibleRows({{ $blocks->pluck('id')->values()->toJson() }})" aria-label="{{ __('Select all visible') }}" />
                        </th>
                        <th class="px-4 py-3">{{ __('Block name') }}</th>
                        <th class="px-4 py-3">{{ __('Block slug') }}</th>
                        <th class="px-4 py-3">{{ __('Block type') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3">{{ __('Preview') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($blocks as $block)
                        <tr wire:key="bf-row-{{ $block->id }}">
                            <td class="px-4 py-3">
                                <input type="checkbox" class="rounded border-[var(--border-panel-soft)]" wire:click="toggleBulkRow({{ $block->id }})" @checked($this->isBulkRowSelected($block->id)) aria-label="{{ __('Select row') }}" />
                            </td>
                            <td class="px-4 py-3 font-medium text-[var(--text-primary)]">
                                {{ $block->block_name }}
                                @if ($block->is_managed)
                                    <span class="ml-2 rounded-full bg-[rgba(197,160,89,0.12)] px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-mom-gold">{{ __('Managed') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-[var(--text-muted)]">{{ $block->block_slug }}</td>
                            <td class="px-4 py-3 text-[var(--text-secondary)]">{{ $block->block_type ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    wire:click="toggleActive({{ $block->id }})"
                                    class="text-xs font-semibold uppercase tracking-wide {{ $block->is_active ? 'text-[var(--success)]' : 'text-[var(--text-muted)]' }}"
                                >
                                    {{ $block->is_active ? __('Active') : __('Inactive') }}
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <button type="button" wire:click="openPreview({{ $block->id }})" class="text-mom-gold hover:underline">{{ __('Live preview') }}</button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" wire:click="startEdit({{ $block->id }})" class="mr-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">{{ __('Edit') }}</button>
                                <button type="button" wire:click="copyBlockContext({{ $block->id }})" class="mr-2 text-mom-gold hover:underline">{{ __('Copy context') }}</button>
                                <button type="button" wire:click="duplicateBlock({{ $block->id }})" class="mr-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">{{ __('Duplicate') }}</button>
                                <button
                                    type="button"
                                    class="mr-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]"
                                    x-data="{ code: {{ \Illuminate\Support\Js::from($block->code) }} }"
                                    @click="navigator.clipboard.writeText(code)"
                                >{{ __('Copy code') }}</button>
                                <button
                                    type="button"
                                    wire:click="removeBlock({{ $block->id }})"
                                    @php
                                        $removeConfirm = $block->is_managed
                                            ? __('Remove this managed block from the database? It will return after php artisan blocks:sync unless you change Git templates.')
                                            : __('Remove this block? Any page or blog that still uses slug “:slug” will show an empty slot until you change the token.', ['slug' => $block->block_slug]);
                                    @endphp
                                    wire:confirm="{{ $removeConfirm }}"
                                    class="text-[var(--danger)] hover:underline"
                                >{{ __('Remove') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-[var(--text-muted)]">
                                {{ $search !== '' ? __('No blocks match your search.') : __('No blocks yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $blocks->links() }}
        </div>
    @else
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-[var(--text-primary)]">{{ $editingId ? __('Edit block') : __('Create block') }}</h2>
            <div class="flex flex-wrap items-center gap-3">
                @if ($editingId)
                    <button type="button" wire:click="copyBlockContext({{ $editingId }})" class="text-sm font-semibold text-mom-gold hover:underline">{{ __('Copy block context') }}</button>
                @endif
                <button type="button" wire:click="cancelForm" class="mom-subtext text-[var(--text-muted)] hover:text-[var(--text-primary)]">{{ __('Back to list') }}</button>
            </div>
        </div>

        <div class="space-y-8">
            @include('livewire.site-architect.partials.architect-save-approvals')

            @if ($editingManaged)
                <div class="mom-card border border-[rgba(197,160,89,0.35)] bg-[rgba(197,160,89,0.08)] px-4 py-3 text-sm text-[var(--text-secondary)]">
                    <p class="font-semibold text-mom-gold">{{ __('Git-managed block') }}</p>
                    <p class="mt-1">{{ __('Markup lives in resources/views/blocks and syncs via php artisan blocks:sync. You can also edit copy below or in') }}
                        <a href="{{ route('site-architect.block-studio.index', ['block' => $block_slug]) }}" class="text-mom-gold underline">{{ \App\Support\SiteArchitectNavigation::LABEL_SECTION_CONTENT }}</a>.
                    </p>
                </div>
            @endif
            @if ($showMarketingCopy)
                <section class="mom-card p-6">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-[var(--text-muted)]">{{ __('Marketing copy') }}</h3>
                    <p class="mom-subtext mt-2 text-sm">{{ __('Saved to block settings and used by the Blade template (e.g. eyebrow above the headline). Schema follows the block slug or the included blocks.* view name.') }}</p>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        @foreach ($contentSchema as $key => $field)
                            <label class="block {{ ($field['type'] ?? 'text') === 'textarea' ? 'md:col-span-2' : '' }}">
                                <span class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ $field['label'] ?? $key }}</span>
                                @if (($field['type'] ?? 'text') === 'textarea')
                                    <textarea wire:model="block_content.{{ $key }}" rows="3" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]"></textarea>
                                @else
                                    <input type="text" wire:model="block_content.{{ $key }}" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]" />
                                @endif
                            </label>
                        @endforeach
                    </div>
                </section>
            @endif
            <section class="mom-card p-6">
                <div class="grid gap-8 md:grid-cols-2">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Block name') }}</label>
                            <input type="text" wire:model.live="block_name" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]" />
                            @error('block_name') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Block slug') }}</label>
                            <input type="text" wire:model="block_slug" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-sm text-[var(--text-primary)]" />
                            @error('block_slug') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Description') }}</label>
                            <textarea wire:model="description" rows="3" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]" placeholder="{{ __('Optional') }}"></textarea>
                        </div>
                        @include('livewire.site-architect.partials.service-insert-controls', [
                            'services' => $services,
                            'serviceCatalogNonce' => $serviceCatalogNonce,
                            'showManageLink' => true,
                        ])
                        @include('livewire.site-architect.partials.module-insert-controls', [
                            'moduleOptions' => $moduleOptions,
                            'wireModel' => 'module_choice',
                            'appendAction' => 'appendModuleToken',
                        ])
                        <div>
                            <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Code (HTML / Blade)') }}</label>
                            <textarea wire:model.live.debounce.400ms="code" rows="16" @readonly($editingManaged) @class(['mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs text-[var(--text-primary)]', 'opacity-70' => $editingManaged])></textarea>
                            @error('code') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                        </div>
                        @include('livewire.site-architect.partials.block-custom-css-field', ['wireModel' => 'custom_css'])
                        <div>
                            <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('JSON schema') }}</label>
                            <textarea wire:model="schema_json_input" rows="6" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs" placeholder="{{ __('Optional') }}"></textarea>
                            @error('schema_json_input') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Block type') }}</label>
                        <select wire:model.live="block_type" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]">
                            <option value="">{{ __('Select type…') }}</option>
                            @foreach ($typesByGroup as $groupLabel => $types)
                                <optgroup label="{{ $groupLabel }}">
                                    @foreach ($types as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="mom-subtext mt-3">{{ __('Types organize the library only; they do not affect rendering.') }}</p>
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-6 border-t border-[color:var(--border-tabstrip-divider)] pt-6">
                    <label class="flex cursor-pointer items-center gap-3 text-sm text-[var(--text-secondary)]">
                        <input type="checkbox" wire:model="is_active" class="rounded border-[rgba(255,255,255,0.15)]" />
                        {{ __('Active') }}
                    </label>
                    <button type="button" wire:click="saveBlock" class="rounded-mom-chrome bg-[var(--accent-gold)] px-5 py-2.5 text-sm font-semibold text-[#120f0d]">{{ __('Save block') }}</button>
                    <button type="button" wire:click="cancelForm" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-5 py-2.5 text-sm text-[var(--text-secondary)]">{{ __('Cancel') }}</button>
                    @if ($editingId)
                        <button
                            type="button"
                            wire:click="removeBlock({{ $editingId }})"
                            wire:confirm="{{ $editingManaged
                                ? __('Remove this managed block from the database? It will return after php artisan blocks:sync unless you change Git templates.')
                                : __('Remove this block? Any page or blog that still uses this slug will show an empty slot until you change the token.') }}"
                            class="rounded-mom-chrome border border-[rgba(226,92,92,0.35)] px-5 py-2.5 text-sm font-semibold text-[var(--danger)]"
                        >{{ __('Remove block') }}</button>
                    @endif
                </div>
            </section>
        </div>
    @endif

    @if ($previewOpen)
        <div class="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 p-4" wire:click.self="closePreview">
            <div class="mom-card max-h-[90vh] w-full max-w-4xl overflow-hidden p-0" @click.stop>
                <div class="flex items-center justify-between border-b border-[color:var(--border-tabstrip-divider)] px-6 py-4">
                    <h4 class="mom-section-title">{{ __('Live preview') }}</h4>
                    <button type="button" wire:click="closePreview" class="text-sm text-[var(--text-muted)] hover:text-[var(--text-primary)]">{{ __('Close') }}</button>
                </div>
                <div class="custom-scrollbar max-h-[calc(90vh-5rem)] overflow-y-auto bg-[var(--bg-app)] px-6 py-6">
                    @if ($previewError !== '')
                        <p class="text-sm text-[var(--danger)]">{{ $previewError }}</p>
                    @else
                        <div class="preview-surface text-[var(--text-primary)]">
                            {!! $previewHtml !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <x-bulk.action-modal :open="$bulkModalOpen" :preview="$bulkGovernancePreview" />
</div>
