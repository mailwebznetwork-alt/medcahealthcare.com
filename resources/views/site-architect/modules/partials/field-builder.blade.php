@props([
    'fieldTypes',
    'schemaFormId' => null,
    'showValueColumn' => false,
    'embedded' => false,
])

@php
    /** @var array<string, string> $fieldTypes */
    $schemaFormAttr = $schemaFormId ? 'form="'.$schemaFormId.'"' : '';
@endphp

<div @class(['mom-card p-6' => ! $embedded])>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="mom-section-title">{{ __('Custom fields') }}</h3>
            <p class="mom-subtext mt-2">{{ __('These fields drive the database columns and admin record forms.') }}</p>
        </div>
        <button
            type="button"
            class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)]"
            @click="addField()"
        >
            {{ __('Add field') }}
        </button>
    </div>

    <div class="mt-6 overflow-x-auto" x-show="fields.length > 0" x-cloak>
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="border-b border-[rgba(255,255,255,0.08)] text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">
                    <th class="px-3 py-2">{{ __('Label') }}</th>
                    <th class="px-3 py-2">{{ __('Field name (column)') }}</th>
                    <th class="px-3 py-2">{{ __('Type') }}</th>
                    <th class="px-3 py-2">{{ __('Required') }}</th>
                    @if ($showValueColumn)
                        <th class="px-3 py-2">{{ __('Value') }}</th>
                    @endif
                    <th class="px-3 py-2 text-right">{{ __('Remove') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[rgba(255,255,255,0.06)]">
                <template x-for="(field, index) in fields" :key="index">
                    <tr class="align-top">
                        <td class="px-3 py-3">
                            <input
                                type="text"
                                class="block w-full min-w-[8rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner"
                                :name="`fields[${index}][label]`"
                                {!! $schemaFormAttr !!}
                                x-model="field.label"
                                @input="syncFieldName(index)"
                                @if (! $embedded) required @endif
                            >
                        </td>
                        <td class="px-3 py-3">
                            <input
                                type="text"
                                class="block w-full min-w-[8rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 font-mono text-sm text-[var(--text-primary)] shadow-mom-inner"
                                :name="`fields[${index}][field_name]`"
                                {!! $schemaFormAttr !!}
                                x-model="field.field_name"
                                pattern="^[a-z][a-z0-9_]*$"
                                @if (! $embedded) required @endif
                            >
                        </td>
                        <td class="px-3 py-3">
                            <select
                                class="block w-full min-w-[7rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner"
                                :name="`fields[${index}][field_type]`"
                                {!! $schemaFormAttr !!}
                                x-model="field.field_type"
                                @if (! $embedded) required @endif
                            >
                                @foreach ($fieldTypes as $typeKey => $typeLabel)
                                    <option value="{{ $typeKey }}">{{ __($typeLabel) }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-3 py-3">
                            <input type="hidden" :name="`fields[${index}][is_required]`" {!! $schemaFormAttr !!} value="0">
                            <label class="flex items-center gap-2 text-sm text-[var(--text-secondary)]">
                                <input
                                    type="checkbox"
                                    :name="`fields[${index}][is_required]`"
                                    {!! $schemaFormAttr !!}
                                    value="1"
                                    x-model="field.is_required"
                                    class="h-4 w-4 rounded border-[rgba(255,255,255,0.12)] bg-[rgba(28,22,18,0.75)] text-mom-gold"
                                >
                                {{ __('Required') }}
                            </label>
                        </td>
                        @if ($showValueColumn)
                            <td class="px-3 py-3">
                                <template x-if="{{ $embedded ? 'field.id' : 'field.field_name' }}">
                                    <div>
                                        <template x-if="field.field_type === 'textarea'">
                                            <textarea
                                                class="block w-full min-w-[10rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner"
                                                rows="2"
                                                :name="`custom_fields[${field.field_name}]`"
                                                x-model="field.value"
                                            ></textarea>
                                        </template>
                                        <template x-if="field.field_type === 'boolean'">
                                            <div class="flex items-center gap-2">
                                                <input type="hidden" :name="`custom_fields[${field.field_name}]`" value="0">
                                                <input
                                                    type="checkbox"
                                                    :name="`custom_fields[${field.field_name}]`"
                                                    value="1"
                                                    x-model="field.value"
                                                    class="h-4 w-4 rounded border-[rgba(255,255,255,0.12)] bg-[rgba(28,22,18,0.75)] text-mom-gold"
                                                >
                                                <span class="text-xs text-[var(--text-muted)]">{{ __('Enabled') }}</span>
                                            </div>
                                        </template>
                                        <template x-if="!['textarea', 'boolean', 'select', 'number', 'date', 'email', 'url'].includes(field.field_type)">
                                            <input
                                                type="text"
                                                class="block w-full min-w-[10rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner"
                                                :name="`custom_fields[${field.field_name}]`"
                                                x-model="field.value"
                                            >
                                        </template>
                                        <template x-if="field.field_type === 'number'">
                                            <input
                                                type="number"
                                                step="any"
                                                class="block w-full min-w-[10rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner"
                                                :name="`custom_fields[${field.field_name}]`"
                                                x-model="field.value"
                                            >
                                        </template>
                                        <template x-if="field.field_type === 'date'">
                                            <input
                                                type="date"
                                                class="block w-full min-w-[10rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner"
                                                :name="`custom_fields[${field.field_name}]`"
                                                x-model="field.value"
                                            >
                                        </template>
                                        <template x-if="field.field_type === 'email'">
                                            <input
                                                type="email"
                                                class="block w-full min-w-[10rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner"
                                                :name="`custom_fields[${field.field_name}]`"
                                                x-model="field.value"
                                            >
                                        </template>
                                        <template x-if="field.field_type === 'url'">
                                            <input
                                                type="url"
                                                class="block w-full min-w-[10rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner"
                                                :name="`custom_fields[${field.field_name}]`"
                                                x-model="field.value"
                                            >
                                        </template>
                                        <template x-if="field.field_type === 'select'">
                                            <select
                                                class="block w-full min-w-[10rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner"
                                                :name="`custom_fields[${field.field_name}]`"
                                                x-model="field.value"
                                            >
                                                <option value="">{{ __('Select…') }}</option>
                                                <template x-for="option in (field.options || '').split(/[,\n]/).map(o => o.trim()).filter(Boolean)" :key="option">
                                                    <option :value="option" x-text="option"></option>
                                                </template>
                                            </select>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="{{ $embedded ? '!field.id' : '!field.field_name' }}">
                                    <span class="text-xs text-[var(--text-muted)]">
                                        @if ($embedded)
                                            {{ __('Save custom fields to enter a value on this record.') }}
                                        @else
                                            {{ __('Save field name first') }}
                                        @endif
                                    </span>
                                </template>
                            </td>
                        @endif
                        <td class="px-3 py-3 text-right">
                            <input type="hidden" :name="`fields[${index}][id]`" {!! $schemaFormAttr !!} x-model="field.id" x-show="field.id">
                            <button type="button" class="text-xs font-semibold text-[var(--danger)] hover:underline" @click="removeField(index)">{{ __('Remove') }}</button>
                        </td>
                    </tr>
                    <tr x-show="field.field_type === 'select'" class="align-top">
                        <td class="px-3 pb-3" :colspan="{{ $showValueColumn ? 6 : 5 }}">
                            <label class="text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">{{ __('Select options (comma or newline separated)') }}</label>
                            <textarea
                                class="mt-2 block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner"
                                rows="2"
                                :name="`fields[${index}][options]`"
                                {!! $schemaFormAttr !!}
                                x-model="field.options"
                            ></textarea>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <p class="mom-subtext mt-4" x-show="fields.length === 0" x-cloak>
        {{ __('No custom fields yet. Press Add field when you need to define one.') }}
    </p>

    @if (! $embedded && ($errors->has('fields') || $errors->has('fields.*')))
        <p class="mom-body-text mt-4 text-[var(--danger)]">{{ __('Please fix the field definitions above.') }}</p>
    @endif
</div>
