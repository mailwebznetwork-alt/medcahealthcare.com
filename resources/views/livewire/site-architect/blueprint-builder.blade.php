<div class="space-y-6">
    <x-admin.card :title="__('Blueprint Builder')">
        <p class="mom-body-text mb-6 text-[var(--text-secondary)]">
            {{ __('Generate standard Pages and block order from an industry blueprint. Output remains fully editable in Site Architect → Pages.') }}
        </p>

        @if ($statusMessage)
            <p class="mom-body-text mb-4 text-[var(--success)]" role="status">{{ $statusMessage }}</p>
        @endif
        @if ($errorMessage)
            <p class="mom-body-text mb-4 text-[var(--danger)]" role="alert">{{ $errorMessage }}</p>
        @endif

        <form wire:submit="generate" class="grid gap-4 md:grid-cols-2">
            <label class="block">
                <span class="mom-label">{{ __('Industry') }}</span>
                <select wire:model.live="industry" class="mom-input w-full">
                    @foreach ($industries as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mom-label">{{ __('Blueprint') }}</span>
                <select wire:model="blueprint_slug" class="mom-input w-full">
                    @foreach ($blueprintOptions as $option)
                        <option value="{{ $option['slug'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mom-label">{{ __('Style pack') }}</span>
                <select wire:model.live="style_pack_slug" class="mom-input w-full">
                    @foreach ($stylePackOptions as $option)
                        <option value="{{ $option['slug'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mom-label">{{ __('Theme preset') }}</span>
                <select wire:model="theme_preset_slug" class="mom-input w-full">
                    @foreach ($themePresetOptions as $slug)
                        <option value="{{ $slug }}">{{ $slug }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block md:col-span-2">
                <span class="mom-label">{{ __('Layout') }}</span>
                <select wire:model="layout_preset" class="mom-input w-full">
                    @foreach ($layoutPresets as $key => $preset)
                        <option value="{{ $key }}">{{ $preset['label'] ?? $key }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex items-center gap-2 md:col-span-2">
                <input type="checkbox" wire:model="activate_generated_pages" class="rounded border-[var(--border-panel-soft)]" />
                <span class="text-sm text-[var(--text-primary)]">{{ __('Activate generated pages immediately (opt-in)') }}</span>
            </label>
            <div class="md:col-span-2 flex flex-wrap gap-2">
                <button type="submit" class="mom-cta-compact mom-cta-primary" wire:loading.attr="disabled">
                    {{ __('Generate pages (draft)') }}
                </button>
                <a href="{{ route('settings.appearance') }}" class="mom-cta-compact mom-cta-ghost">{{ __('Theme settings') }}</a>
                <a href="{{ route('site-architect.pages.index') }}" class="mom-cta-compact mom-cta-ghost">{{ __('Edit pages') }}</a>
                <button type="button" wire:click="previewStylePack" class="mom-cta-compact mom-cta-ghost">{{ __('Preview style pack') }}</button>
                <button type="button" wire:click="clearStylePackPreview" class="mom-cta-compact mom-cta-ghost">{{ __('Clear style pack preview') }}</button>
            </div>
        </form>

        @if ($generatedSlugs !== [])
            <ul class="mom-body-text mt-6 list-disc pl-5 text-[var(--text-secondary)]">
                @foreach ($generatedSlugs as $slug)
                    <li><code>{{ $slug }}</code></li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>

    @if ($recentGenerations->isNotEmpty())
        <x-admin.card :title="__('Recent generations')">
            <ul class="space-y-2 text-sm text-[var(--text-secondary)]">
                @foreach ($recentGenerations as $gen)
                    <li>
                        {{ $gen->blueprint_slug }} · {{ $gen->style_pack_slug }} ·
                        {{ $gen->created_at?->diffForHumans() }}
                    </li>
                @endforeach
            </ul>
        </x-admin.card>
    @endif
</div>
