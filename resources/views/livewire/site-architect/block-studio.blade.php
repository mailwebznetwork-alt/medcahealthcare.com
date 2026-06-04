<div class="space-y-6">
    @if ($statusMessage)<p class="text-sm text-[var(--success)]" role="status">{{ $statusMessage }}</p>@endif
    @if ($errorMessage)<p class="text-sm text-[var(--danger)]" role="alert">{{ $errorMessage }}</p>@endif

    <x-admin.card :title="__('Edit section content')">
        <p class="mom-body-text mb-4 text-sm text-[var(--text-secondary)]">{{ __('Pick the section you want to edit, then use the tabs below for wording, images, and layout. Phone and WhatsApp numbers come from Settings → Global content.') }}</p>
        @if ($block_slug !== '' && ($sectionDisplayName ?? '') !== '')
            <p class="mb-4 text-sm font-semibold text-[var(--text-primary)]">{{ __('Editing: :name', ['name' => $sectionDisplayName]) }}</p>
        @endif
        <label class="block max-w-md">
            <span class="mom-label">{{ __('Section') }}</span>
            <select wire:model.live="block_slug" class="mom-input w-full">
                @foreach ($sectionPickerGroups as $category => $sections)
                    <optgroup label="{{ $category }}">
                        @foreach ($sections as $section)
                            <option value="{{ $section['slug'] }}">{{ $section['display_name'] }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </label>
    </x-admin.card>

    <div class="flex flex-wrap gap-2">
        @php
            $panels = [];
            if (count($contentSchema) > 0) {
                $panels['content'] = __('Section content');
            }
            $panels['media'] = __('Images & media');
            $panels['section'] = __('Section layout');
            $panels['style'] = __('Style variant');
        @endphp
        @foreach ($panels as $key => $label)
            <button type="button" wire:click="$set('activePanel', '{{ $key }}')" @class(['mom-cta-compact', 'mom-cta-primary' => $activePanel === $key, 'mom-cta-ghost' => $activePanel !== $key])>{{ $label }}</button>
        @endforeach
    </div>

    <x-admin.card>
        @if ($activePanel === 'content')
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($contentSchema as $key => $field)
                    <label class="block {{ ($field['type'] ?? 'text') === 'textarea' ? 'md:col-span-2' : '' }}">
                        <span class="mom-label">{{ $field['label'] ?? $key }}</span>
                        @if (($field['type'] ?? 'text') === 'textarea')
                            <textarea wire:model="content.{{ $key }}" rows="3" class="mom-input mt-2 w-full text-sm"></textarea>
                        @else
                            <input type="text" wire:model="content.{{ $key }}" class="mom-input mt-2 w-full text-sm" />
                        @endif
                    </label>
                @endforeach
            </div>
            @if (count($contentSchema) === 0)
                <p class="mom-subtext text-sm">{{ __('This section has no editable text fields. Try Images & media or ask a developer to add a content schema.') }}</p>
            @endif
        @elseif ($activePanel === 'media')
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($mediaSlots as $slot)
                    <div class="rounded-lg border border-[var(--border-panel-soft)] p-3">
                        <span class="mom-label">{{ str_replace('_', ' ', ucfirst($slot)) }}</span>
                        @if (! empty($media[$slot]))
                            <p class="mt-1 truncate text-xs text-[var(--text-secondary)]">{{ $media[$slot] }}</p>
                        @endif
                        <input type="file" wire:model="uploads.{{ $slot }}" class="mom-input mt-2 w-full text-xs" />
                        <input type="text" wire:model="media.{{ $slot }}" placeholder="{{ __('Path or URL') }}" class="mom-input mt-2 w-full text-xs" />
                        <button type="button" wire:click="removeMedia('{{ $slot }}')" class="mom-cta-compact mom-cta-ghost mt-2 text-xs">{{ __('Remove') }}</button>
                    </div>
                @endforeach
            </div>
        @elseif ($activePanel === 'section')
            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($sectionKeys as $key)
                    @if (str_starts_with($key, 'visibility_'))
                        <label class="flex items-center gap-2 rounded border border-[var(--border-panel-soft)] px-3 py-2">
                            <input type="checkbox" wire:model="section.{{ $key }}" class="rounded" />
                            <span class="text-sm">{{ str_replace('_', ' ', ucfirst($key)) }}</span>
                        </label>
                    @else
                        <label class="block">
                            <span class="mom-label text-xs">{{ str_replace('_', ' ', ucfirst($key)) }}</span>
                            <input type="text" wire:model="section.{{ $key }}" class="mom-input w-full text-sm" />
                        </label>
                    @endif
                @endforeach
            </div>
        @else
            <label class="block max-w-xs">
                <span class="mom-label">{{ __('Style variant') }}</span>
                <select wire:model="style_variant" class="mom-input w-full">
                    @foreach ($styleVariants as $variant)
                        <option value="{{ $variant }}">{{ $variant }}</option>
                    @endforeach
                </select>
            </label>
        @endif

        <div class="mt-6 flex flex-wrap gap-2">
            <button type="button" wire:click="preview" class="mom-cta-compact mom-cta-ghost">{{ __('Preview section') }}</button>
            <button type="button" wire:click="saveDraft" class="mom-cta-compact mom-cta-primary">{{ __('Save section') }}</button>
        </div>
    </x-admin.card>

    @if ($preview_html)
        <x-admin.card :title="__('Section preview')">
            <div class="rounded-lg border border-[var(--border-panel-soft)] bg-white p-4 text-slate-900">{!! $preview_html !!}</div>
        </x-admin.card>
    @endif
</div>
