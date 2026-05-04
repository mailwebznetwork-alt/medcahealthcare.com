<div>
    @if (session('status'))
        <div class="mom-card mb-6 border border-[rgba(98,195,120,0.28)] bg-[rgba(98,195,120,0.08)] px-4 py-3 text-sm text-[var(--success)]" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($mode === 'list')
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-[var(--text-primary)]">{{ __('Block Factory') }}</h2>
            <a
                href="{{ route('site-architect.block-factory.index') }}?create=1"
                wire:click.prevent="startCreate"
                role="button"
                class="inline-flex cursor-pointer rounded-mom-chrome border border-[rgba(197,160,89,0.28)] bg-[rgba(197,160,89,0.1)] px-4 py-2 text-sm font-medium text-mom-gold no-underline"
            >
                {{ __('Create block') }}
            </a>
        </div>

        <div class="mom-card overflow-x-auto p-0">
            <table class="mom-table w-full min-w-[960px] text-left text-sm">
                <thead>
                    <tr>
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
                            <td class="px-4 py-3 font-medium text-[var(--text-primary)]">{{ $block->block_name }}</td>
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
                                <button type="button" wire:click="duplicateBlock({{ $block->id }})" class="mr-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">{{ __('Duplicate') }}</button>
                                <button
                                    type="button"
                                    class="mr-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]"
                                    x-data="{ code: {{ \Illuminate\Support\Js::from($block->code) }} }"
                                    @click="navigator.clipboard.writeText(code)"
                                >{{ __('Copy code') }}</button>
                                <button
                                    type="button"
                                    wire:click="deleteBlock({{ $block->id }})"
                                    wire:confirm="{{ __('Delete this block? Any page or blog that still references this slug will show an empty slot until you change the token.') }}"
                                    class="text-[var(--danger)] hover:underline"
                                >{{ __('Delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-[var(--text-muted)]">{{ __('No blocks yet.') }}</td>
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
            <button type="button" wire:click="cancelForm" class="mom-subtext text-[var(--text-muted)] hover:text-[var(--text-primary)]">{{ __('Back to list') }}</button>
        </div>

        <div class="space-y-8">
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
                        <div>
                            <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Code (HTML / Blade)') }}</label>
                            <textarea wire:model="code" rows="16" class="mt-2 w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 font-mono text-xs text-[var(--text-primary)]"></textarea>
                            @error('code') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
                        </div>
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

                <div class="mt-8 flex flex-wrap items-center gap-6 border-t border-[var(--border-panel-soft)] pt-6">
                    <label class="flex cursor-pointer items-center gap-3 text-sm text-[var(--text-secondary)]">
                        <input type="checkbox" wire:model="is_active" class="rounded border-[rgba(255,255,255,0.15)]" />
                        {{ __('Active') }}
                    </label>
                    <button type="button" wire:click="saveBlock" class="rounded-mom-chrome bg-[var(--accent-gold)] px-5 py-2.5 text-sm font-semibold text-[#120f0d]">{{ __('Save block') }}</button>
                    <button type="button" wire:click="cancelForm" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-5 py-2.5 text-sm text-[var(--text-secondary)]">{{ __('Cancel') }}</button>
                </div>
            </section>
        </div>
    @endif

    @if ($previewOpen)
        <div class="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 p-4" wire:click.self="closePreview">
            <div class="mom-card max-h-[90vh] w-full max-w-4xl overflow-hidden p-0" @click.stop>
                <div class="flex items-center justify-between border-b border-[var(--border-panel-soft)] px-6 py-4">
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
</div>
