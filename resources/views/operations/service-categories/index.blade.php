<x-operations.workspace>
    @if (session('status') === 'service-category-created')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Category created.') }}</p>
    @endif
    @if (session('status') === 'service-category-updated')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Category updated.') }}</p>
    @endif
    @if (session('status') === 'service-category-deleted')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Category removed.') }}</p>
    @endif

    <form method="get" action="{{ route('operations.service-categories.index') }}" class="mom-card mb-6 flex flex-wrap items-end gap-4 p-5">
        <div class="min-w-[12rem] flex-1">
            <x-input-label for="q" :value="__('Search')" variant="mom" />
            <x-text-input id="q" name="q" type="search" class="mt-2 block w-full" :value="$filters['q'] ?? ''" placeholder="{{ __('Name, code, description…') }}" variant="mom" />
        </div>
        <div>
            <x-input-label for="active" :value="__('Status')" variant="mom" />
            <select id="active" name="active" class="rounded-mom-chrome mt-2 block min-w-[10rem] border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('Any') }}</option>
                <option value="1" @selected(($filters['active'] ?? '') === '1')>{{ __('Active') }}</option>
                <option value="0" @selected(($filters['active'] ?? '') === '0')>{{ __('Inactive') }}</option>
            </select>
        </div>
        <div>
            <x-input-label for="parent_id" :value="__('Parent')" variant="mom" />
            <select id="parent_id" name="parent_id" class="rounded-mom-chrome mt-2 block min-w-[12rem] border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('Any parent') }}</option>
                <option value="0" @selected(($filters['parent_id'] ?? '') === '0')>{{ __('Top level only') }}</option>
                @foreach ($parentOptions as $parent)
                    <option value="{{ $parent->id }}" @selected((string) ($filters['parent_id'] ?? '') === (string) $parent->id)>{{ $parent->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <x-secondary-button variant="mom" type="submit">{{ __('Filter') }}</x-secondary-button>
            <a href="{{ route('operations.service-categories.index') }}" class="mom-cta-ghost">{{ __('Reset') }}</a>
        </div>
    </form>

    <div class="mom-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="mom-table min-w-full text-left text-sm">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-wide text-[var(--text-secondary)]">
                    <tr>
                        <th class="px-5 py-3">{{ __('Name') }}</th>
                        <th class="px-5 py-3">{{ __('Code') }}</th>
                        <th class="px-5 py-3">{{ __('Parent') }}</th>
                        <th class="px-5 py-3">{{ __('Services') }}</th>
                        <th class="px-5 py-3">{{ __('Sort') }}</th>
                        <th class="px-5 py-3">{{ __('Status') }}</th>
                        <th class="px-5 py-3 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)]">
                    @forelse ($categories as $category)
                        <tr class="text-[var(--text-primary)]">
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
                            <td colspan="7" class="px-5 py-10 text-center text-[var(--text-muted)]">
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
</x-operations.workspace>
