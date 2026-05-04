<div>
    @if (session('status'))
        <div class="mom-card mb-6 border border-[rgba(98,195,120,0.28)] bg-[rgba(98,195,120,0.08)] px-4 py-3 text-sm text-[var(--success)]" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($mode === 'list')
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-[var(--text-primary)]">{{ __('Pages') }}</h2>
            <button
                type="button"
                wire:click="startCreate"
                class="rounded-mom-chrome border border-[rgba(197,160,89,0.28)] bg-[rgba(197,160,89,0.1)] px-4 py-2 text-sm font-medium text-mom-gold"
            >
                {{ __('Create page') }}
            </button>
        </div>

        <div class="mom-card overflow-x-auto p-0">
            <table class="mom-table w-full min-w-[640px] text-left text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3">{{ __('Page name') }}</th>
                        <th class="px-4 py-3">{{ __('Slug') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3">{{ __('Preview') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pages as $page)
                        <tr wire:key="page-row-{{ $page->id }}">
                            <td class="px-4 py-3 font-medium text-[var(--text-primary)]">{{ $page->title }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-[var(--text-muted)]">{{ $page->slug }}</td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    wire:click="toggleActive({{ $page->id }})"
                                    class="text-xs font-semibold uppercase tracking-wide {{ $page->is_active ? 'text-[var(--success)]' : 'text-[var(--text-muted)]' }}"
                                >
                                    {{ $page->is_active ? __('Live') : __('Off') }}
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <a
                                    href="{{ route('site-architect.pages.preview', $page) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-mom-gold hover:underline"
                                >{{ __('Preview') }}</a>
                                @if ($page->is_active)
                                    <span class="text-[var(--text-muted)]">·</span>
                                    <a
                                        href="{{ route('pages.public', $page) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="text-mom-gold hover:underline"
                                    >{{ __('Live') }}</a>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" wire:click="startEdit({{ $page->id }})" class="mr-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">{{ __('Edit') }}</button>
                                <button type="button" wire:click="duplicatePage({{ $page->id }})" class="mr-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">{{ __('Duplicate') }}</button>
                                <button
                                    type="button"
                                    wire:click="deletePage({{ $page->id }})"
                                    wire:confirm="{{ __('Delete this page?') }}"
                                    class="text-[var(--danger)] hover:underline"
                                >{{ __('Delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-[var(--text-muted)]">{{ __('No pages yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $pages->links() }}
        </div>
    @else
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-[var(--text-primary)]">{{ $editingId ? __('Edit page') : __('New page') }}</h2>
            <button type="button" wire:click="cancelForm" class="mom-subtext text-[var(--text-muted)] hover:text-[var(--text-primary)]">{{ __('Back to list') }}</button>
        </div>

        <div class="space-y-8">
            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Basic') }}</h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Page title') }}</label>
                        <input type="text" wire:model.live="title" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]" />
                        @error('title') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Slug') }}</label>
                        <input type="text" wire:model="slug" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-sm text-[var(--text-primary)]" />
                        @error('slug') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" wire:model="is_active" id="page-active" class="rounded border-[rgba(255,255,255,0.15)]" />
                        <label for="page-active" class="text-sm text-[var(--text-secondary)]">{{ __('Live') }}</label>
                    </div>
                </div>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Blocks & modules (structure)') }}</h3>
                <p class="mom-subtext mb-4 max-w-2xl">{{ __('Order defines page structure. Blocks hold Blade/HTML; modules resolve via config.') }}</p>

                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="openBlockModal(null)" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)]">
                        {{ __('Add block') }}
                    </button>
                    <div class="flex flex-wrap items-center gap-2">
                        <select wire:model="module_choice" class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]">
                            <option value="">{{ __('Insert module…') }}</option>
                            @foreach ($modules as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
                        <button type="button" wire:click="appendModule" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)]">{{ __('Add module line') }}</button>
                    </div>
                    @error('module_choice') <span class="text-xs text-[var(--danger)]">{{ $message }}</span> @enderror
                </div>

                <ul class="mt-6 space-y-2">
                    @foreach ($contentParts as $idx => $part)
                        <li wire:key="part-{{ $idx }}-{{ $part['type'] }}-{{ $part['slug'] }}" class="flex flex-wrap items-center justify-between gap-2 rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] px-3 py-2 font-mono text-xs text-[var(--text-secondary)]">
                            <span>{{ '{{'.$part['type'].':'.$part['slug'].str_repeat('}', 2) }}</span>
                            <span class="flex flex-wrap gap-1">
                                @if ($part['type'] === 'block')
                                    <button type="button" wire:click="editBlockFromPart({{ $idx }})" class="text-mom-gold hover:underline">{{ __('Edit') }}</button>
                                @endif
                                <button type="button" wire:click="movePartUp({{ $idx }})" class="hover:text-[var(--text-primary)]">{{ __('Up') }}</button>
                                <button type="button" wire:click="movePartDown({{ $idx }})" class="hover:text-[var(--text-primary)]">{{ __('Down') }}</button>
                                <button type="button" wire:click="removePart({{ $idx }})" class="text-[var(--danger)] hover:underline">{{ __('Remove') }}</button>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('SEO') }}</h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Meta title') }}</label>
                        <input type="text" wire:model="meta_title" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Meta description') }}</label>
                        <textarea wire:model="meta_description" rows="3" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Keywords') }}</label>
                        <input type="text" wire:model="keywords" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                    </div>
                    @foreach (['h1' => __('H1'), 'h2' => __('H2'), 'h3' => __('H3'), 'h4' => __('H4'), 'h5' => __('H5'), 'h6' => __('H6')] as $field => $label)
                        <div>
                            <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ $label }}</label>
                            <input type="text" wire:model="{{ $field }}" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('AEO') }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Question') }}</label>
                        <textarea wire:model="aeo_question" rows="2" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Answer snippet') }}</label>
                        <textarea wire:model="aeo_answer" rows="4" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm"></textarea>
                    </div>
                </div>
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('GEO (PIN codes)') }}</h3>
                <p class="mom-subtext mb-4">{{ __('Select coverage PINs; area and city are read-only from the directory.') }}</p>
                <div class="custom-scrollbar max-h-64 overflow-y-auto rounded-mom-chrome border border-[var(--border-panel-soft)] p-3">
                    @foreach ($pinCodes as $pc)
                        <label class="flex cursor-pointer gap-3 border-b border-[var(--border-panel-soft)] py-2 last:border-0">
                            <input type="checkbox" wire:model.live="selectedPinIds" value="{{ $pc->id }}" class="mt-1 rounded border-[rgba(255,255,255,0.15)]" />
                            <span class="text-sm">
                                <span class="font-mono text-[var(--text-primary)]">{{ $pc->pincode }}</span>
                                — {{ $pc->area_name }}, {{ $pc->city ?? '—' }}
                            </span>
                        </label>
                    @endforeach
                </div>

                @foreach ($selectedPinIds as $pid)
                    @php $pinRow = $pinCodes->firstWhere('id', (int) $pid); @endphp
                    @if ($pinRow)
                        <div class="mom-card mt-4 border border-[var(--border-panel-soft)] p-4" wire:key="geo-{{ $pid }}">
                            <p class="font-mono text-sm text-[var(--text-primary)]">{{ $pinRow->pincode }}</p>
                            <p class="mom-subtext mt-1">{{ __('Area') }}: {{ $pinRow->area_name }} · {{ __('City') }}: {{ $pinRow->city ?? '—' }}</p>
                            <p class="mom-micro mt-2">{{ __('Suggested phrases') }}</p>
                            <ul class="mom-subtext mt-1 list-inside list-disc">
                                @foreach (\App\Livewire\SiteArchitect\Pages::defaultKeywordHints($pinRow) as $hint)
                                    <li>{{ $hint }}</li>
                                @endforeach
                            </ul>
                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                <label class="flex items-center gap-2 text-sm text-[var(--text-secondary)]">
                                    <input type="checkbox" wire:model="pinPivot.{{ $pid }}.serviceability" class="rounded border-[rgba(255,255,255,0.15)]" />
                                    {{ __('Serviceability') }}
                                </label>
                                <div>
                                    <label class="block text-xs text-[var(--text-muted)]">{{ __('Delivery charge') }}</label>
                                    <input type="text" wire:model="pinPivot.{{ $pid }}.delivery_charge" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-2 py-1 text-sm" placeholder="0.00" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs text-[var(--text-muted)]">{{ __('Location keywords override') }}</label>
                                    <textarea wire:model="pinPivot.{{ $pid }}.location_keywords" rows="2" class="mt-1 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-2 py-1 text-sm" placeholder="{{ __('Optional; overrides auto-generated GEO phrases') }}"></textarea>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </section>

            <section class="mom-card p-6">
                <h3 class="mom-section-title mb-4">{{ __('Schema & tracking') }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Schema JSON') }}</label>
                        <textarea wire:model="schema_json_input" rows="8" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs"></textarea>
                        @error('schema_json_input') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('GTM code') }}</label>
                        <textarea wire:model="gtm_code" rows="3" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Pixel code') }}</label>
                        <textarea wire:model="pixel_code" rows="3" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs"></textarea>
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <button type="button" wire:click="savePage" class="rounded-mom-chrome bg-[var(--accent-gold)] px-5 py-2.5 text-sm font-semibold text-[#120f0d]">{{ __('Save page') }}</button>
                <button type="button" wire:click="cancelForm" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-5 py-2.5 text-sm text-[var(--text-secondary)]">{{ __('Cancel') }}</button>
            </div>
        </div>
    @endif

    @if ($blockModalOpen)
        <div class="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 p-4" wire:click.self="closeBlockModal">
            <div class="mom-card max-h-[90vh] w-full max-w-2xl overflow-y-auto p-6" @click.stop>
                <h4 class="mom-section-title">{{ $blockEditingSlug ? __('Edit block') : __('New block') }}</h4>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Block name') }}</label>
                        <input type="text" wire:model.live="block_name" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm" />
                        @error('block_name') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Block slug') }}</label>
                        <input
                            type="text"
                            wire:model="block_slug"
                            class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-sm"
                            @if ($blockEditingSlug !== null) disabled @endif
                        />
                        @error('block_slug') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Code (HTML / Blade / Alpine)') }}</label>
                        <textarea wire:model="block_code" rows="14" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs"></textarea>
                        @error('block_code') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-6 flex gap-2">
                    <button type="button" wire:click="saveBlockInModal" class="rounded-mom-chrome bg-[var(--accent-gold)] px-4 py-2 text-sm font-semibold text-[#120f0d]">{{ __('Save block') }}</button>
                    <button type="button" wire:click="closeBlockModal" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-4 py-2 text-sm">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
