<x-operations.workspace>
    @if (session('status'))
        <div class="mom-card mb-6 border border-[rgba(197,160,89,0.22)] bg-[rgba(197,160,89,0.06)] px-4 py-3 text-sm text-[var(--text-secondary)]" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="get" action="{{ route('operations.services.index') }}" class="mom-card mb-6 flex flex-wrap items-end gap-4 p-5">
        <div class="min-w-[12rem] flex-1">
            <x-input-label for="q" :value="__('Search')" variant="mom" />
            <x-text-input id="q" name="q" type="search" class="mt-2 block w-full" :value="$filters['q'] ?? ''" placeholder="{{ __('Title or service code…') }}" variant="mom" />
        </div>
        <div>
            <x-input-label for="publish_status" :value="__('Publish')" variant="mom" />
            <select id="publish_status" name="publish_status" class="rounded-mom-chrome mt-2 block min-w-[10rem] border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('Any') }}</option>
                <option value="draft" @selected(($filters['publish_status'] ?? '') === 'draft')>{{ __('Draft') }}</option>
                <option value="published" @selected(($filters['publish_status'] ?? '') === 'published')>{{ __('Published') }}</option>
            </select>
        </div>
        <div>
            <x-input-label for="active" :value="__('Active')" variant="mom" />
            <select id="active" name="active" class="rounded-mom-chrome mt-2 block min-w-[8rem] border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('Any') }}</option>
                <option value="1" @selected(($filters['active'] ?? '') === '1')>{{ __('Yes') }}</option>
                <option value="0" @selected(($filters['active'] ?? '') === '0')>{{ __('No') }}</option>
            </select>
        </div>
        @isset($categoryOptions)
            <div class="min-w-[14rem]">
                <x-input-label for="category_ids" :value="__('Categories')" variant="mom" />
                <select id="category_ids" name="category_ids[]" multiple class="rounded-mom-chrome mt-2 block min-h-[6rem] w-full border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                    @php $selectedCats = array_map('intval', (array) ($filters['category_ids'] ?? [])); @endphp
                    @foreach ($categoryOptions as $cat)
                        <option value="{{ $cat->id }}" @selected(in_array((int) $cat->id, $selectedCats, true))>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <p class="mom-subtext mt-1 text-xs">{{ __('Hold Ctrl/Cmd to filter by multiple categories.') }}</p>
            </div>
        @endisset
        <div>
            <x-input-label for="featured" :value="__('Featured')" variant="mom" />
            <select id="featured" name="featured" class="rounded-mom-chrome mt-2 block min-w-[8rem] border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('Any') }}</option>
                <option value="1" @selected(($filters['featured'] ?? '') === '1')>{{ __('Yes') }}</option>
                <option value="0" @selected(($filters['featured'] ?? '') === '0')>{{ __('No') }}</option>
            </select>
        </div>
        <div class="flex gap-2">
            <x-secondary-button variant="mom" type="submit">{{ __('Filter') }}</x-secondary-button>
            <a href="{{ route('operations.services.index') }}" class="mom-cta-ghost">{{ __('Reset') }}</a>
        </div>
    </form>

    <div class="mom-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="mom-table min-w-full text-left text-sm">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-wide text-[var(--text-secondary)]">
                    <tr>
                        <th class="px-5 py-3">{{ __('Title') }}</th>
                        <th class="px-5 py-3">{{ __('Service code') }}</th>
                        <th class="px-5 py-3">{{ __('Categories') }}</th>
                        <th class="px-5 py-3">{{ __('Active') }}</th>
                        <th class="px-5 py-3">{{ __('Publish') }}</th>
                        <th class="px-5 py-3">{{ __('Featured') }}</th>
                        <th class="px-5 py-3">{{ __('Price') }}</th>
                        <th class="px-5 py-3">{{ __('Updated') }}</th>
                        <th class="px-5 py-3 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)]">
                    @forelse ($services as $service)
                        <tr class="text-[var(--text-primary)]">
                            <td class="px-5 py-3 font-medium">{{ $service->title }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-[var(--text-secondary)]">{{ $service->service_code }}</td>
                            <td class="px-5 py-3 max-w-[14rem]">
                                @include('operations.services.partials.category-badges', ['service' => $service])
                            </td>
                            <td class="px-5 py-3">
                                <span class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] px-2 py-0.5 text-[11px] uppercase">{{ $service->is_active ? __('Yes') : __('No') }}</span>
                            </td>
                            <td class="px-5 py-3 text-[var(--text-secondary)]">{{ $service->publish_status->value }}</td>
                            <td class="px-5 py-3">{{ $service->is_featured ? __('Yes') : __('No') }}</td>
                            <td class="px-5 py-3 text-[var(--text-secondary)]">{{ $service->price_range ?? '—' }}</td>
                            <td class="px-5 py-3 text-[var(--text-muted)]">{{ $service->updated_at?->diffForHumans() }}</td>
                            <td class="px-5 py-3 text-end">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('operations.services.preview', $service) }}" class="mom-cta-ghost text-[11px]">{{ __('Preview') }}</a>
                                    <a href="{{ route('operations.services.edit', $service) }}" class="mom-cta-ghost text-[11px]">{{ __('Edit') }}</a>
                                    <a href="{{ route('operations.services.duplicate', $service) }}" class="mom-cta-ghost text-[11px]">{{ __('Duplicate') }}</a>
                                    <form action="{{ route('operations.services.destroy', $service) }}" method="post" class="inline" onsubmit="return confirm(@json(__('Delete this service?')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="mom-cta-ghost text-[11px] text-[var(--danger)]">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-10 text-center text-[var(--text-muted)]">
                                <p>{{ __('No services yet.') }}</p>
                                <a href="{{ route('operations.services.create') }}" class="mom-cta-primary mt-4 inline-flex">{{ __('Create service') }}</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($services->hasPages())
            <div class="mom-backend-hairline-t px-5 py-4">
                {{ $services->links() }}
            </div>
        @endif
    </div>
</x-operations.workspace>
