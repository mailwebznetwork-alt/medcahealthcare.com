<div class="space-y-6">
    @if (! $ready)
        <x-admin.card><p class="mom-body-text text-[var(--text-secondary)]">{{ __('Run deployment engine migrations.') }}</p></x-admin.card>
    @else
        @if ($statusMessage)<p class="text-sm text-[var(--success)]" role="status">{{ $statusMessage }}</p>@endif
        @if ($errorMessage)<p class="text-sm text-[var(--danger)]" role="alert">{{ $errorMessage }}</p>@endif

        <div class="grid gap-6 lg:grid-cols-2">
            <x-admin.card :title="__('Create template')">
                <form wire:submit="createPreset" class="grid gap-3">
                    <label class="block"><span class="mom-label">{{ __('Name') }}</span><input type="text" wire:model="preset_name" class="mom-input w-full" /></label>
                    <label class="block"><span class="mom-label">{{ __('Block type') }}</span><input type="text" wire:model="block_type" class="mom-input w-full" /></label>
                    <label class="block"><span class="mom-label">{{ __('Target block slug') }}</span>
                        <select wire:model="target_block_slug" class="mom-input w-full">
                            <option value="">{{ __('— Optional —') }}</option>
                            @foreach ($blocks as $block)
                                <option value="{{ $block->block_slug }}">{{ $block->block_slug }} ({{ $block->block_type }})</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block"><span class="mom-label">{{ __('Style variant') }}</span>
                        <select wire:model="style_variant" class="mom-input w-full">
                            @foreach ($styleVariants as $variant)
                                <option value="{{ $variant }}">{{ $variant }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="mom-cta-compact mom-cta-primary">{{ __('Save template') }}</button>
                </form>
            </x-admin.card>

            <x-admin.card :title="__('Import / export')">
                <textarea wire:model="import_json" rows="8" class="mom-input w-full font-mono text-xs"></textarea>
                <button type="button" wire:click="importPreset" class="mom-cta-compact mom-cta-ghost mt-2">{{ __('Import template') }}</button>
            </x-admin.card>
        </div>

        <x-admin.card :title="__('Saved templates')">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-[var(--text-secondary)]"><th class="py-2">{{ __('Name') }}</th><th>{{ __('Type') }}</th><th>{{ __('Target') }}</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($presets as $preset)
                            <tr class="border-t border-[color:var(--border-tabstrip-divider)]">
                                <td class="py-2 font-medium">{{ $preset->name }}</td>
                                <td>{{ $preset->block_type }}</td>
                                <td><code>{{ $preset->target_block_slug ?? '—' }}</code></td>
                                <td class="py-2 text-right">
                                    <div class="flex flex-wrap justify-end gap-1">
                                        <button type="button" wire:click="previewPreset({{ $preset->id }})" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Preview') }}</button>
                                        <button type="button" wire:click="applyPreset({{ $preset->id }})" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Apply') }}</button>
                                        <button type="button" wire:click="clonePreset({{ $preset->id }})" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Clone') }}</button>
                                        <button type="button" wire:click="exportPreset({{ $preset->id }})" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Export') }}</button>
                                        @if (! $preset->is_builtin)
                                            <button type="button" wire:click="deletePreset({{ $preset->id }})" wire:confirm="{{ __('Delete this preset?') }}" class="mom-cta-compact mom-cta-ghost text-xs text-[var(--danger)]">{{ __('Delete') }}</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-[var(--text-secondary)]">{{ __('No templates yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>

        @if ($preview_html)
            <x-admin.card :title="__('Template preview')">
                <div class="rounded-lg border border-[var(--border-panel-soft)] bg-white p-4 text-slate-900">{!! $preview_html !!}</div>
            </x-admin.card>
        @endif
    @endif
</div>
