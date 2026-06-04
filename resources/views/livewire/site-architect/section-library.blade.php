<div class="space-y-6">
    @if (! $ready)
        <x-admin.card><p class="mom-body-text">{{ __('Run migrations for Legacy Sections.') }}</p></x-admin.card>
    @else
        @if ($statusMessage)<p class="mom-body-text text-[var(--success)]">{{ $statusMessage }}</p>@endif
        @if ($errorMessage)<p class="mom-body-text text-[var(--danger)]">{{ $errorMessage }}</p>@endif

        @unless (config('platform_composition.section_library_deprecated'))
            <x-admin.card :title="__('Create section')">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block"><span class="mom-label">{{ __('Name') }}</span><input type="text" wire:model="create_name" class="mom-input w-full" /></label>
                    <label class="block"><span class="mom-label">{{ __('Block slugs (comma-separated)') }}</span><input type="text" wire:model="create_blocks" class="mom-input w-full" placeholder="hero-home,stats-row,cta-banner" /></label>
                </div>
                <button type="button" wire:click="createSection" class="mom-cta-compact mom-cta-primary mt-3">{{ __('Create section') }}</button>
            </x-admin.card>
        @endunless

        <x-admin.card :title="__('Legacy Sections')">
            <p class="mom-body-text mb-4 text-[var(--text-secondary)]">{{ __('Multi-block reusable sections. Insert with standard block tokens or') }} <code class="text-mom-gold">@{{ section:slug }}</code> {{ __('on pages.') }}</p>
            <ul class="space-y-3">
                @forelse ($sections as $section)
                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-mom-chrome border border-[var(--border-panel-soft)] p-4">
                        <div>
                            <p class="font-semibold text-[var(--text-primary)]">{{ $section->name }}</p>
                            <p class="text-xs text-[var(--text-secondary)]"><code>{{ $section->slug }}</code> · {{ count($section->blocks_json ?? []) }} {{ __('blocks') }}</p>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <button type="button" wire:click="previewSection('{{ $section->slug }}')" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Preview') }}</button>
                            <button type="button" wire:click="exportSection('{{ $section->slug }}')" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Export') }}</button>
                            <button type="button" wire:click="cloneSection('{{ $section->slug }}')" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Clone') }}</button>
                            @if (! $section->is_builtin)
                                <button type="button" wire:click="deleteSection('{{ $section->slug }}')" wire:confirm="{{ __('Delete section?') }}" class="mom-cta-compact mom-cta-ghost text-xs text-[var(--danger)]">{{ __('Delete') }}</button>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="mom-body-text text-[var(--text-muted)]">{{ __('No sections yet.') }}</li>
                @endforelse
            </ul>
        </x-admin.card>

        @if ($preview_html)
            <x-admin.card :title="__('Section preview')">
                <div class="rounded-lg border border-[var(--border-panel-soft)] bg-white p-4 text-slate-900">{!! $preview_html !!}</div>
            </x-admin.card>
        @endif

        <x-admin.card :title="__('Capture from page')">
            <div class="grid gap-4 md:grid-cols-3">
                <label class="block md:col-span-2"><span class="mom-label">{{ __('Page slug') }}</span><input type="text" wire:model="capture_page_slug" class="mom-input w-full" /></label>
                <label class="block md:col-span-3"><span class="mom-label">{{ __('Section name') }}</span><input type="text" wire:model="capture_name" class="mom-input w-full" /></label>
                <div class="md:col-span-3"><button type="button" wire:click="captureFromPage" class="mom-cta-compact mom-cta-primary">{{ __('Save section') }}</button></div>
            </div>
        </x-admin.card>

        <x-admin.card :title="__('Insert into page')">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block"><span class="mom-label">{{ __('Page') }}</span>
                    <select wire:model="insert_page_slug" class="mom-input w-full"><option value="">{{ __('Select…') }}</option>
                        @foreach ($pages as $page)<option value="{{ $page->slug }}">{{ $page->title }}</option>@endforeach
                    </select>
                </label>
                <label class="block"><span class="mom-label">{{ __('Section') }}</span>
                    <select wire:model="insert_section_slug" class="mom-input w-full"><option value="">{{ __('Select…') }}</option>
                        @foreach ($sections as $section)<option value="{{ $section->slug }}">{{ $section->name }}</option>@endforeach
                    </select>
                </label>
                <div class="md:col-span-2"><button type="button" wire:click="insertIntoPage" class="mom-cta-compact mom-cta-primary">{{ __('Insert section') }}</button></div>
            </div>
        </x-admin.card>

        <x-admin.card :title="__('Import section JSON')">
            <textarea wire:model="import_json" rows="8" class="mom-input w-full font-mono text-xs"></textarea>
            <button type="button" wire:click="importSection" class="mom-cta-compact mom-cta-ghost mt-2">{{ __('Import section') }}</button>
        </x-admin.card>
    @endif
</div>
