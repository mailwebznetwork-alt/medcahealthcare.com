<div>
    @if (session('status') === 'service-category-created')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Category created.') }}</p>
    @elseif (session('status') === 'service-category-updated')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Category updated.') }}</p>
    @elseif (session('status') === 'service-category-deleted')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Category removed.') }}</p>
    @elseif (session('status'))
        <div class="mom-card mb-6 border border-[rgba(197,160,89,0.22)] bg-[rgba(197,160,89,0.06)] px-4 py-3 text-sm text-[var(--text-secondary)]" role="status">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mom-card mb-6 border border-[rgba(226,92,92,0.35)] bg-[rgba(226,92,92,0.08)] px-4 py-3 text-sm text-[var(--danger)]" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="mom-card mb-6 flex flex-wrap items-end gap-4 p-5">
        <div class="min-w-[12rem] flex-1">
            <x-input-label for="categories-q" :value="__('Search')" variant="mom" />
            <x-text-input id="categories-q" wire:model.live.debounce.300ms="q" type="search" class="mt-2 block w-full" placeholder="{{ __('Name, code, description…') }}" variant="mom" />
        </div>
        <div>
            <x-input-label for="categories-active" :value="__('Status')" variant="mom" />
            <select id="categories-active" wire:model.live="active" class="rounded-mom-chrome mt-2 block min-w-[10rem] border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('Any') }}</option>
                <option value="1">{{ __('Active') }}</option>
                <option value="0">{{ __('Inactive') }}</option>
            </select>
        </div>
        <div>
            <x-input-label for="categories-parent" :value="__('Parent')" variant="mom" />
            <select id="categories-parent" wire:model.live="parentId" class="rounded-mom-chrome mt-2 block min-w-[12rem] border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('Any parent') }}</option>
                <option value="0">{{ __('Top level only') }}</option>
                @foreach ($parentOptions as $parent)
                    <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                @endforeach
            </select>
        </div>
        @can('create', \App\Models\ServiceCategory::class)
            <a href="{{ route('operations.service-categories.create') }}" class="mom-cta-primary">{{ __('Add category') }}</a>
        @endcan
    </div>

    @if ($categories->isNotEmpty())
        <div class="mb-3 flex flex-wrap gap-2 text-xs">
            <button type="button" wire:click="selectAllVisibleRows({{ $categories->pluck('id')->values()->toJson() }})" class="text-mom-gold hover:underline">{{ __('Select all visible') }}</button>
            <button type="button" wire:click="selectAllFilteredRows" class="text-mom-gold hover:underline">{{ __('Select all filtered results') }}</button>
            <button type="button" wire:click="deselectAllRows" class="text-[var(--text-muted)] hover:underline">{{ __('Deselect all') }}</button>
        </div>

        <x-bulk.selection-toolbar
            :count="$this->bulkSelectedCount()"
            :actions="['modify', 'duplicate', 'delete']"
        />
    @endif

    <div class="mom-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="mom-table min-w-full text-left text-sm">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-wide text-[var(--text-secondary)]">
                    <tr>
                        <th class="w-10 px-5 py-3">
                            <input type="checkbox" class="rounded border-[var(--border-panel-soft)]" aria-label="{{ __('Select all visible') }}" wire:click="selectAllVisibleRows({{ $categories->pluck('id')->values()->toJson() }})" />
                        </th>
                        <th class="px-5 py-3">{{ __('Name') }}</th>
                        <th class="px-5 py-3">{{ __('Code') }}</th>
                        <th class="px-5 py-3">{{ __('Parent') }}</th>
                        <th class="px-5 py-3">{{ __('Services (all areas)') }}</th>
                        <th class="px-5 py-3">{{ __('Sort') }}</th>
                        <th class="px-5 py-3">{{ __('Status') }}</th>
                        <th class="px-5 py-3 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)]">
                    @forelse ($categories as $category)
                        <tr wire:key="service-category-row-{{ $category->id }}" class="text-[var(--text-primary)]">
                            <td class="px-5 py-3">
                                <input type="checkbox" class="rounded border-[var(--border-panel-soft)]" wire:click="toggleBulkRow({{ $category->id }})" @checked($this->isBulkRowSelected($category->id)) aria-label="{{ __('Select row') }}" />
                            </td>
                            <td class="px-5 py-3 font-medium">{{ $category->name }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-[var(--text-secondary)]">{{ $category->code }}</td>
                            <td class="px-5 py-3 text-[var(--text-secondary)]">{{ $category->parent?->name ?? '—' }}</td>
                            <td class="px-5 py-3">{{ $category->services_count }}</td>
                            <td class="px-5 py-3 text-[var(--text-muted)]">{{ $category->sort_order }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] px-2 py-0.5 text-[11px] uppercase {{ $category->is_active ? 'text-[var(--success)]' : 'text-[var(--text-muted)]' }}">
                                    {{ $category->is_active ? __('Active') : __('Inactive') }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-end">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if ($category->is_active)
                                        <a href="{{ $category->publicUrl() }}" class="mom-cta-ghost text-[11px]" target="_blank" rel="noopener">{{ __('Public') }}</a>
                                    @endif
                                    <a href="{{ route('operations.service-categories.edit', $category) }}" class="mom-cta-ghost text-[11px]">{{ __('Edit') }}</a>
                                    <form action="{{ route('operations.service-categories.destroy', $category) }}" method="post" class="inline" onsubmit="return confirm(@json(__('Delete this category? Assigned services will be unlinked.')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="mom-cta-ghost text-[11px] text-[var(--danger)]">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-[var(--text-muted)]">
                                <p>{{ __('No categories yet.') }}</p>
                                @can('create', \App\Models\ServiceCategory::class)
                                    <a href="{{ route('operations.service-categories.create') }}" class="mom-cta-primary mt-4 inline-flex">{{ __('Add category') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($categories->hasPages())
            <div class="mom-backend-hairline-t px-5 py-4">
                {{ $categories->links() }}
            </div>
        @endif
    </div>

    <x-bulk.action-modal :open="$bulkModalOpen" :preview="$bulkGovernancePreview" />
</div>
