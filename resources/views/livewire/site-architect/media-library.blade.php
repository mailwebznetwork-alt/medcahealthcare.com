<div class="space-y-8">
    <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-[var(--text-primary)]">{{ __('Media library') }}</h2>
            <p class="mom-subtext mt-1 max-w-2xl text-sm">{{ __('Upload images here, then pick them in Blocks Studio → Media for each section.') }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="mom-card border border-[rgba(98,195,120,0.28)] bg-[rgba(98,195,120,0.08)] px-4 py-3 text-sm text-[var(--success)]" role="status">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mom-card border border-[rgba(239,68,68,0.35)] bg-[rgba(239,68,68,0.08)] px-4 py-3 text-sm text-[var(--danger)]" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <section class="mom-card p-6">
        <h3 class="mom-section-title mb-4">{{ __('Upload') }}</h3>
        <div
            class="rounded-mom-chrome border border-dashed border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] px-6 py-10 text-center transition-colors"
            x-data="{ drag: false }"
            x-on:dragover.prevent="drag = true"
            x-on:dragleave.prevent="drag = false"
            x-on:drop.prevent="
                drag = false;
                const i = $refs.uploader;
                i.files = $event.dataTransfer.files;
                i.dispatchEvent(new Event('change', { bubbles: true }));
            "
            :class="{ 'border-mom-gold bg-[rgba(197,160,89,0.06)]': drag }"
        >
            <input type="file" wire:model="uploads" multiple class="hidden" x-ref="uploader" id="media-upload-input" />
            <label for="media-upload-input" class="cursor-pointer text-sm text-[var(--text-secondary)]">
                {{ __('Drag and drop files here, or') }}
                <span class="text-mom-gold">{{ __('choose files') }}</span>
            </label>
            <p class="mom-subtext mt-2">{{ __('Images are optimized once at upload. Max 50 MB per file.') }}</p>
            <div wire:loading wire:target="uploads" class="mom-subtext mt-3 text-sm">{{ __('Processing…') }}</div>
        </div>
        @error('uploads.*') <p class="mt-2 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
    </section>

    <div class="flex flex-wrap items-end gap-4">
        <div class="min-w-[200px] flex-1">
            <label class="mom-micro text-[var(--text-muted)]">{{ __('Search') }}</label>
            <input
                type="search"
                wire:model.live.debounce.400ms="search"
                class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm"
                placeholder="{{ __('File name, title, or alt text…') }}"
            />
        </div>
        <div class="min-w-[160px]">
            <label class="mom-micro text-[var(--text-muted)]">{{ __('Type') }}</label>
            <select wire:model.live="filter_type" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm">
                <option value="">{{ __('All') }}</option>
                <option value="image">{{ __('Images') }}</option>
                <option value="video">{{ __('Videos') }}</option>
                <option value="document">{{ __('Documents') }}</option>
            </select>
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-[1fr_minmax(300px,380px)]">
        <div>
            <h3 class="mom-section-title mb-4">{{ __('Library') }}</h3>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                @forelse ($items as $item)
                    <button
                        type="button"
                        wire:key="media-tile-{{ $item->id }}"
                        wire:click="selectMedia({{ $item->id }})"
                        @class([
                            'rounded-mom-chrome border p-2 text-left transition-colors duration-320',
                            'border-mom-gold bg-[rgba(197,160,89,0.08)]' => $selectedId === $item->id,
                            'border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] hover:border-[rgba(197,160,89,0.35)]' => $selectedId !== $item->id,
                        ])
                    >
                        <div class="aspect-video overflow-hidden rounded-mom-chrome bg-black/30">
                            @if ($item->file_type === 'image')
                                <img
                                    src="{{ $item->publicUrlFor($item->small_path ?? $item->webp_path ?? $item->optimized_path ?? $item->file_path) }}"
                                    alt=""
                                    class="h-full w-full object-cover"
                                    loading="lazy"
                                />
                            @elseif ($item->file_type === 'video')
                                <div class="flex h-full items-center justify-center text-xs text-[var(--text-muted)]">{{ __('Video') }}</div>
                            @else
                                <div class="flex h-full items-center justify-center text-xs text-[var(--text-muted)]">{{ __('File') }}</div>
                            @endif
                        </div>
                        <p class="mt-2 truncate text-xs font-medium text-[var(--text-primary)]">{{ $item->file_name }}</p>
                        <p class="truncate text-[11px] uppercase tracking-wide text-[var(--text-muted)]">{{ $item->file_type }}</p>
                    </button>
                @empty
                    <p class="col-span-full py-10 text-center text-sm text-[var(--text-muted)]">{{ __('No media found.') }}</p>
                @endforelse
            </div>
            <div class="mt-6">
                {{ $items->links() }}
            </div>
        </div>

        <aside class="lg:sticky lg:top-24 lg:self-start">
            @if ($selected)
                <div class="mom-card space-y-4 p-6">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="mom-section-title">{{ __('Details') }}</h3>
                        <button type="button" wire:click="closeDetail" class="text-xs text-[var(--text-muted)] hover:text-[var(--text-primary)]">{{ __('Close') }}</button>
                    </div>

                    <div class="overflow-hidden rounded-mom-chrome border border-[var(--border-panel-soft)] bg-black/20">
                        @if ($selected->file_type === 'image')
                            <img
                                src="{{ $selected->publicUrlFor($selected->webp_path ?? $selected->optimized_path ?? $selected->file_path) }}"
                                alt="{{ $selected->alt_text }}"
                                class="max-h-64 w-full object-contain"
                            />
                        @elseif ($selected->file_type === 'video')
                            <video src="{{ $selected->publicUrlFor($selected->file_path) }}" controls class="w-full"></video>
                        @else
                            <div class="px-4 py-8 text-center text-sm text-[var(--text-muted)]">{{ __('No preview') }}</div>
                        @endif
                    </div>

                    <p class="break-all font-mono text-xs text-[var(--text-secondary)]" title="{{ __('Primary asset URL') }}">{{ $selected->preferredImageUrl() }}</p>

                    <div class="space-y-3">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Title') }}</label>
                        <input type="text" wire:model="edit_title" class="w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Alt text') }} @if ($selected->file_type === 'image')<span class="text-[var(--danger)]">*</span>@endif</label>
                        <input type="text" wire:model="edit_alt_text" class="w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                        @error('edit_alt_text') <p class="text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Description') }}</label>
                        <textarea wire:model="edit_description" rows="3" class="w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm"></textarea>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="saveMetadata" class="rounded-mom-chrome bg-[var(--accent-gold)] px-4 py-2 text-sm font-semibold text-[#120f0d]">{{ __('Save metadata') }}</button>
                        <button
                            type="button"
                            wire:click="deleteMedia({{ $selected->id }})"
                            wire:confirm="{{ __('Delete this media and all generated files?') }}"
                            class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-4 py-2 text-sm text-[var(--danger)]"
                        >{{ __('Delete') }}</button>
                    </div>

                    <div class="border-t border-[var(--border-panel-soft)] pt-4">
                        <p class="mom-micro mb-2 text-[var(--text-muted)]">{{ __('Copy') }}</p>
                        <div class="flex flex-wrap gap-2">
                            @if ($selected->file_type === 'image')
                                <button
                                    type="button"
                                    class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-1.5 text-xs text-[var(--text-primary)]"
                                    x-data="{ u: {{ \Illuminate\Support\Js::from($selected->preferredImageUrl()) }} }"
                                    @click="navigator.clipboard.writeText(u)"
                                >{{ __('Primary URL (WebP → JPEG → original)') }}</button>
                                <button
                                    type="button"
                                    class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-1.5 text-xs"
                                    x-data="{ h: {{ \Illuminate\Support\Js::from($selected->snippetHtmlBasic()) }} }"
                                    @click="navigator.clipboard.writeText(h)"
                                >{{ __('HTML basic') }}</button>
                                <button
                                    type="button"
                                    class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-1.5 text-xs"
                                    x-data="{ h: {{ \Illuminate\Support\Js::from($selected->snippetHtmlResponsive()) }} }"
                                    @click="navigator.clipboard.writeText(h)"
                                >{{ __('HTML responsive') }}</button>
                                <button
                                    type="button"
                                    class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-1.5 text-xs"
                                    x-data="{ h: {{ \Illuminate\Support\Js::from($selected->snippetHtmlBlurPlaceholder()) }} }"
                                    @click="navigator.clipboard.writeText(h)"
                                >{{ __('HTML blur') }}</button>
                            @else
                                <button
                                    type="button"
                                    class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-1.5 text-xs"
                                    x-data="{ u: {{ \Illuminate\Support\Js::from($selected->publicUrlFor($selected->file_path)) }} }"
                                    @click="navigator.clipboard.writeText(u)"
                                >{{ __('File URL') }}</button>
                            @endif
                        </div>
                        <p class="mom-subtext mt-2">{{ __('Set CDN_ENABLED and CDN_URL in .env to use a CDN base for these URLs.') }}</p>
                    </div>
                </div>
            @else
                <div class="mom-card p-6 text-sm text-[var(--text-muted)]">
                    {{ __('Select an item to edit metadata and copy URLs or HTML.') }}
                </div>
            @endif
        </aside>
    </div>
</div>
