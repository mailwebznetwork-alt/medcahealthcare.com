<div>
    @if (! $ready)
        <x-admin.card>
            <p class="mom-body-text text-[var(--text-secondary)]">{{ __('Run database migrations to enable global content variables.') }}</p>
        </x-admin.card>
    @else
        <x-admin.card :title="__('Global Content Variables')">
            <p class="mom-body-text mb-6 text-[var(--text-secondary)]">
                {{ __('Use tokens like') }}
                <code class="text-mom-gold">@{{ company_name }}</code>
                {{ __('or') }}
                <code class="text-mom-gold">@{{ mission_statement }}</code>
                {{ __('in pages, blocks, blueprints, and sections. Updates apply everywhere on the next render.') }}
            </p>
            @if ($statusMessage)
                <p class="mom-body-text mb-4 text-[var(--success)]" role="status">{{ $statusMessage }}</p>
            @endif
            @if ($errorMessage)
                <p class="mom-body-text mb-4 text-[var(--danger)]" role="alert">{{ $errorMessage }}</p>
            @endif

            <div class="mb-6 rounded-lg border border-[var(--border-panel-soft)] p-4">
                <h3 class="mb-2 text-sm font-semibold text-[var(--text-primary)]">{{ __('Live preview') }}</h3>
                <dl class="grid gap-2 text-sm md:grid-cols-3">
                    <div><dt class="text-[var(--text-secondary)]">{{ __('Headline') }}</dt><dd>{{ $preview['headline'] }}</dd></div>
                    <div><dt class="text-[var(--text-secondary)]">{{ __('Contact') }}</dt><dd>{{ $preview['contact'] }}</dd></div>
                    <div><dt class="text-[var(--text-secondary)]">{{ __('Primary CTA') }}</dt><dd>{{ $preview['cta'] }}</dd></div>
                </dl>
            </div>

            <form wire:submit="save" class="space-y-8">
                @foreach ($grouped as $groupKey => $group)
                    <div>
                        <h3 class="mb-1 text-base font-semibold text-[var(--text-primary)]">{{ $group['label'] }}</h3>
                        @if ($group['description'] !== '')
                            <p class="mom-body-text mb-4 text-sm text-[var(--text-secondary)]">{{ $group['description'] }}</p>
                        @endif
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($group['fields'] as $key => $row)
                                <label @class(['block', 'md:col-span-2' => ($row['type'] ?? 'text') === 'textarea'])>
                                    <span class="mom-label">{{ $row['label'] }}</span>
                                    <code class="mb-1 block text-xs text-mom-gold">{{ '{' . '{' . $key . '}' . '}' }}</code>
                                    @if (! empty($row['hint']))
                                        <span class="mb-1 block text-xs text-[var(--text-secondary)]">{{ $row['hint'] }}</span>
                                    @endif
                                    @if (($row['type'] ?? 'text') === 'textarea')
                                        <textarea wire:model="values.{{ $key }}" rows="4" class="mom-input w-full"></textarea>
                                    @else
                                        <input type="text" wire:model="values.{{ $key }}" class="mom-input w-full" />
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="mom-cta-compact mom-cta-primary">{{ __('Save variables') }}</button>
                    <button type="button" wire:click="saveVersion" class="mom-cta-compact mom-cta-ghost">{{ __('Save & version') }}</button>
                    <button type="button" wire:click="exportJson" class="mom-cta-compact mom-cta-ghost">{{ __('Export JSON') }}</button>
                </div>
            </form>
        </x-admin.card>

        <x-admin.card :title="__('Import / export')" class="mt-6">
            <textarea wire:model="import_json" rows="8" class="mom-input w-full font-mono text-xs"></textarea>
            <button type="button" wire:click="importJson" class="mom-cta-compact mom-cta-ghost mt-2">{{ __('Import JSON') }}</button>
        </x-admin.card>

        @if ($snapshots->isNotEmpty())
            <x-admin.card :title="__('Version history')" class="mt-6">
                <ul class="space-y-2 text-sm">
                    @foreach ($snapshots as $snapshot)
                        <li class="flex flex-wrap items-center justify-between gap-2">
                            <span>{{ __('Version') }} {{ $snapshot->version }} · {{ $snapshot->created_at?->diffForHumans() }}</span>
                            <button type="button" wire:click="restoreVersion({{ $snapshot->id }})" wire:confirm="{{ __('Restore this version?') }}" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Restore') }}</button>
                        </li>
                    @endforeach
                </ul>
            </x-admin.card>
        @endif
    @endif
</div>
