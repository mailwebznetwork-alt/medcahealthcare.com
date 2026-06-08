<div>
    @if ($open)
        <div class="fixed inset-0 z-[99995] flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/60" wire:click="close"></div>
            <div class="relative z-10 flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] shadow-2xl">
                <div class="flex items-center justify-between border-b border-[color:var(--border-tabstrip-divider)] px-5 py-4">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">{{ __('Media Library') }}</h3>
                    <button type="button" wire:click="close" class="text-sm text-[var(--text-muted)] hover:text-[var(--text-primary)]">{{ __('Close') }}</button>
                </div>

                <div class="custom-scrollbar flex-1 overflow-y-auto px-5 py-4">
                    @if (session('picker_error'))
                        <p class="mb-3 text-sm text-[var(--danger)]">{{ session('picker_error') }}</p>
                    @endif

                    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search…') }}" class="mom-input text-sm" />
                        <select wire:model.live="filter_type" class="mom-input text-sm">
                            <option value="">{{ __('All types') }}</option>
                            <option value="image">{{ __('Images') }}</option>
                            <option value="video">{{ __('Videos') }}</option>
                            <option value="document">{{ __('Documents') }}</option>
                        </select>
                        <select wire:model.live="filter_category" class="mom-input text-sm">
                            <option value="">{{ __('All categories') }}</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                        <input type="text" wire:model.live.debounce.300ms="filter_tag" placeholder="{{ __('Filter tag') }}" class="mom-input text-sm" />
                    </div>

                    <div class="mb-4 rounded-mom-chrome border border-dashed border-[var(--border-panel-soft)] p-4 text-center">
                        <input type="file" wire:model="uploads" multiple class="text-sm" />
                        <p class="mom-subtext mt-1 text-xs">{{ __('Or upload — processed into the library automatically.') }}</p>
                        <div wire:loading wire:target="uploads" class="mom-subtext mt-2 text-xs">{{ __('Processing…') }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @forelse ($items as $item)
                            <button
                                type="button"
                                wire:key="pick-{{ $item->id }}"
                                wire:click="select({{ $item->id }})"
                                @class([
                                    'rounded-mom-chrome border p-2 text-left transition',
                                    'border-mom-gold bg-[rgba(197,160,89,0.08)]' => $highlightId === $item->id,
                                    'border-[var(--border-panel-soft)] hover:border-mom-gold/50' => $highlightId !== $item->id,
                                ])
                            >
                                <div class="aspect-video overflow-hidden rounded bg-black/20">
                                    @if ($item->file_type === 'image')
                                        <img src="{{ $item->publicUrlFor($item->thumbnail_path ?? $item->small_path ?? $item->webp_path ?? $item->file_path) }}" alt="" class="h-full w-full object-cover" loading="lazy" />
                                    @else
                                        <div class="flex h-full items-center justify-center text-xs text-[var(--text-muted)]">{{ $item->file_type }}</div>
                                    @endif
                                </div>
                                <p class="mt-2 truncate text-xs font-medium">{{ $item->file_name }}</p>
                                <p class="text-[10px] text-[var(--text-muted)]">
                                    @if ($item->width && $item->height){{ $item->width }}×{{ $item->height }} · @endif
                                    {{ __('Used') }}: {{ $item->usages_count }}
                                    @if ($item->image_seo_score !== null) · SEO {{ $item->image_seo_score }}% @endif
                                </p>
                            </button>
                        @empty
                            <p class="col-span-full py-8 text-center text-sm text-[var(--text-muted)]">{{ __('No media found.') }}</p>
                        @endforelse
                    </div>
                    <div class="mt-4">{{ $items->links() }}</div>
                </div>
            </div>
        </div>
    @endif
</div>
