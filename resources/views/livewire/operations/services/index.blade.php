<div>
    @if (session('status'))
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
            <x-input-label for="services-q" :value="__('Search')" variant="mom" />
            <x-text-input id="services-q" wire:model.live.debounce.300ms="q" type="search" class="mt-2 block w-full" placeholder="{{ __('Title or service code…') }}" variant="mom" />
        </div>
        <div>
            <x-input-label for="services-publish" :value="__('Publish')" variant="mom" />
            <select id="services-publish" wire:model.live="publishStatus" class="rounded-mom-chrome mt-2 block min-w-[10rem] border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('Any') }}</option>
                <option value="draft">{{ __('Draft') }}</option>
                <option value="published">{{ __('Published') }}</option>
            </select>
        </div>
        <div>
            <x-input-label for="services-active" :value="__('Active')" variant="mom" />
            <select id="services-active" wire:model.live="active" class="rounded-mom-chrome mt-2 block min-w-[8rem] border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('Any') }}</option>
                <option value="1">{{ __('Yes') }}</option>
                <option value="0">{{ __('No') }}</option>
            </select>
        </div>
        <div class="min-w-[14rem]">
            <x-input-label for="services-categories" :value="__('Categories')" variant="mom" />
            <select id="services-categories" wire:model.live="categoryIds" multiple class="rounded-mom-chrome mt-2 block min-h-[6rem] w-full border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                @foreach ($categoryOptions as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="services-featured" :value="__('Featured')" variant="mom" />
            <select id="services-featured" wire:model.live="featured" class="rounded-mom-chrome mt-2 block min-w-[8rem] border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('Any') }}</option>
                <option value="1">{{ __('Yes') }}</option>
                <option value="0">{{ __('No') }}</option>
            </select>
        </div>
    </div>

    @if ($services->isNotEmpty())
        <x-bulk.selection-links
            :visible-ids="$services->pluck('id')->all()"
            :total-count="$this->bulkTotalSelectableCount()"
        />

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
                            <input type="checkbox" class="rounded border-[var(--border-panel-soft)]" aria-label="{{ __('Select all') }}" wire:click="selectAllRows" />
                        </th>
                        <th class="px-5 py-3">{{ __('Title') }}</th>
                        <th class="px-5 py-3">{{ __('Service code') }}</th>
                        <th class="px-5 py-3">{{ __('Categories') }}</th>
                        <th class="px-5 py-3">{{ __('Sub-services') }}</th>
                        <th class="px-5 py-3">{{ __('Areas (GEO)') }}</th>
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
                        <tr wire:key="service-row-{{ $service->id }}" class="text-[var(--text-primary)]">
                            <td class="px-5 py-3">
                                <input type="checkbox" class="rounded border-[var(--border-panel-soft)]" wire:click="toggleBulkRow({{ $service->id }})" @checked($this->isBulkRowSelected($service->id)) aria-label="{{ __('Select row') }}" />
                            </td>
                            <td class="px-5 py-3 font-medium">{{ $service->title }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-[var(--text-secondary)]">{{ $service->service_code }}</td>
                            <td class="max-w-[14rem] px-5 py-3">
                                @include('operations.services.partials.category-badges', ['service' => $service])
                            </td>
                            <td class="px-5 py-3">
                                @if ($service->sub_services_count > 0)
                                    <a href="{{ route('operations.services.edit', ['service' => $service, 'tab' => 'sub_services']) }}" class="font-medium text-mom-gold hover:underline">
                                        {{ number_format($service->sub_services_count) }}
                                    </a>
                                @else
                                    <a href="{{ route('operations.services.edit', ['service' => $service, 'tab' => 'sub_services']) }}" class="text-[var(--text-muted)] hover:text-mom-gold hover:underline" title="{{ __('No sub-services yet') }}">
                                        —
                                    </a>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($service->pincodes_count > 0)
                                    <a href="{{ route('operations.services.edit', ['service' => $service, 'tab' => 'geo']) }}" class="font-medium text-mom-gold hover:underline" title="{{ __('Serviceable pincodes') }}">
                                        {{ number_format($service->pincodes_count) }}
                                    </a>
                                @else
                                    <a href="{{ route('operations.services.edit', ['service' => $service, 'tab' => 'geo']) }}" class="text-[var(--text-muted)] hover:text-mom-gold hover:underline" title="{{ __('No GEO areas — assign pincodes') }}">
                                        —
                                    </a>
                                @endif
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
                            <td colspan="12" class="px-5 py-10 text-center text-[var(--text-muted)]">
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

    <x-bulk.action-modal :open="$bulkModalOpen" :preview="$bulkGovernancePreview" />
</div>
