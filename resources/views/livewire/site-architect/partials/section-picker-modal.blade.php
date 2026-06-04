@php
    $categories = $sectionPickerCategories ?? config('page_builder_sections.picker_categories', []);
@endphp
<div class="fixed inset-0 z-[210] flex items-center justify-center bg-black/65 p-4" wire:click.self="closeSectionPicker">
    <div class="mom-card flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden p-0" @click.stop role="dialog" aria-modal="true" aria-labelledby="section-picker-title">
        <div class="border-b border-[var(--border-panel-soft)] px-5 py-4">
            <h4 id="section-picker-title" class="mom-section-title">{{ __('Add section') }}</h4>
            <p class="mom-subtext mt-1 text-sm">{{ __('Choose a section type. You can change the wording after adding it.') }}</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <label class="block min-w-[12rem] flex-1">
                    <span class="sr-only">{{ __('Search') }}</span>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="sectionPickerSearch"
                        placeholder="{{ __('Search sections…') }}"
                        class="mom-input w-full text-sm"
                    />
                </label>
                <label class="block">
                    <span class="sr-only">{{ __('Category') }}</span>
                    <select wire:model.live="sectionPickerCategory" class="mom-input text-sm">
                        <option value="all">{{ __('All categories') }}</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar px-5 py-4">
            @forelse ($sectionPickerGroups as $category => $sections)
                <div class="mb-6" wire:key="picker-cat-{{ $category }}">
                    <p class="mom-micro mb-3">{{ $category }}</p>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($sections as $section)
                            <button
                                type="button"
                                wire:key="picker-{{ $section['slug'] }}"
                                wire:click="appendSection('{{ $section['slug'] }}')"
                                class="flex flex-col rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] p-3 text-left transition hover:border-mom-gold/50 hover:bg-[var(--bg-hover)]"
                            >
                                @include('site-architect.partials.section-preview-tile', ['previewKey' => $section['preview_key']])
                                <p class="mt-3 text-sm font-semibold text-[var(--text-primary)]">
                                    {{ $section['display_name'] }}
                                    @if ($section['recommended'] ?? false)
                                        <span class="ml-1 rounded bg-mom-gold/15 px-1.5 py-0.5 text-[10px] font-medium text-mom-gold">{{ __('Suggested') }}</span>
                                    @endif
                                </p>
                                <p class="mom-subtext mt-1 line-clamp-2 text-xs leading-relaxed">{{ $section['description'] }}</p>
                                <p class="mt-2 text-[10px] uppercase tracking-wide text-[var(--text-muted)]">{{ $section['picker_category'] }}</p>
                            </button>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-[var(--text-muted)]">{{ __('No sections match your search. Try another category or ask an admin to sync blocks.') }}</p>
            @endforelse
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-[var(--border-panel-soft)] px-5 py-3">
            @if ($canUseDeveloperBlockTools ?? false)
                <button type="button" wire:click="openDeveloperBlockModal" class="text-xs text-[var(--text-muted)] underline hover:text-mom-gold">
                    {{ __('Developer: create block with code') }}
                </button>
            @else
                <span></span>
            @endif
            <button type="button" wire:click="closeSectionPicker" class="mom-cta-compact mom-cta-ghost">{{ __('Cancel') }}</button>
        </div>
    </div>
</div>
