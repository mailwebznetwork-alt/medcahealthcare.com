@props([
    'module',
    'values',
])

@php
    /** @var \App\Models\Module $module */
    /** @var object $values */
    use App\Models\FieldDefinition;

    $module->loadMissing('fieldDefinitions');
    $canManageSchema = auth()->user()?->canManageDynamicModuleSchema() ?? false;
    $fieldTypes = FieldDefinition::typeLabels();
    $schemaFormId = 'legacy-module-schema-form-'.$module->slug;

    $seedFields = old('fields');
    if ($seedFields === null) {
        $seedFields = $module->fieldDefinitions->map(fn (FieldDefinition $f) => [
            'id' => $f->id,
            'label' => $f->label,
            'field_name' => $f->field_name,
            'field_type' => $f->field_type,
            'is_required' => $f->is_required,
            'options' => implode(', ', $f->selectOptions()),
            'value' => old('custom_fields.'.$f->field_name, $values->{$f->field_name} ?? ''),
        ])->values()->all();
    }
@endphp

@if ($canManageSchema || $module->fieldDefinitions->isNotEmpty())
    <section class="mom-card p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="mom-section-title">{{ __('Custom fields') }}</h3>
                <p class="mom-subtext mt-2 max-w-2xl">
                    @if ($canManageSchema)
                        {{ __('Custom fields are optional. Define schema with Save custom fields when needed; values on saved fields submit with Save changes.') }}
                    @else
                        {{ __('Additional attributes defined in Module Builder for :module.', ['module' => $module->name]) }}
                    @endif
                </p>
            </div>
            @if ($canManageSchema)
                <span class="rounded-full bg-[rgba(197,160,89,0.12)] px-3 py-1 text-xs font-semibold text-mom-gold">{{ __('Schema manager') }}</span>
            @endif
        </div>

        @if (session('status') === 'legacy-module-fields-updated')
            <p class="mom-body-text mt-4 text-[var(--success)]" role="status">{{ __('Custom fields updated.') }}</p>
        @endif

        @if ($canManageSchema)
            @push('schema-forms')
                <form id="{{ $schemaFormId }}" method="post" action="{{ route('operations.managed-modules.fields.update', $module) }}" class="hidden" aria-hidden="true">
                    @csrf
                    @method('PUT')
                </form>
            @endpush

            <div
                class="mt-6"
                x-data="{
                    fields: @js($seedFields),
                    addField() {
                        this.fields.push({ label: '', field_name: '', field_type: 'text', is_required: false, options: '', value: '' });
                    },
                    removeField(index) {
                        this.fields.splice(index, 1);
                    },
                    syncFieldName(index) {
                        const label = this.fields[index].label || '';
                        const prev = this.fields[index]._prevLabel || '';
                        if (! this.fields[index].field_name || this.fields[index].field_name === this.slugify(prev)) {
                            this.fields[index].field_name = this.slugify(label);
                        }
                        this.fields[index]._prevLabel = label;
                    },
                    slugify(value) {
                        return String(value || '').toLowerCase().trim()
                            .replace(/[^a-z0-9]+/g, '_')
                            .replace(/^_+|_+$/g, '')
                            .replace(/^(\d)/, 'f_$1');
                    },
                }"
            >
                @include('site-architect.modules.partials.field-builder', [
                    'fieldTypes' => $fieldTypes,
                    'schemaFormId' => $schemaFormId,
                    'showValueColumn' => true,
                    'embedded' => true,
                ])

                <div class="mt-4 flex flex-wrap gap-3">
                    <x-primary-button variant="mom" type="submit" :form="$schemaFormId">{{ __('Save custom fields') }}</x-primary-button>
                </div>

                @if ($errors->has('fields') || $errors->has('fields.*'))
                    <p class="mom-body-text mt-3 text-[var(--danger)]">{{ __('Please fix the field definitions above, then use Save custom fields.') }}</p>
                @endif
            </div>
        @else
            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-[rgba(255,255,255,0.08)] text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">
                            <th class="px-3 py-2">{{ __('Label') }}</th>
                            <th class="px-3 py-2">{{ __('Field name (column)') }}</th>
                            <th class="px-3 py-2">{{ __('Type') }}</th>
                            <th class="px-3 py-2">{{ __('Required') }}</th>
                            <th class="px-3 py-2">{{ __('Value') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgba(255,255,255,0.06)]">
                        @foreach ($module->fieldDefinitions as $field)
                            @php
                                $cellValue = old('custom_fields.'.$field->field_name, $values->{$field->field_name} ?? null);
                            @endphp
                            <tr class="align-top">
                                <td class="px-3 py-3 text-[var(--text-primary)]">{{ $field->label }}</td>
                                <td class="px-3 py-3 font-mono text-xs text-[var(--text-secondary)]">{{ $field->field_name }}</td>
                                <td class="px-3 py-3 text-[var(--text-secondary)]">{{ __(FieldDefinition::typeLabels()[$field->field_type] ?? $field->field_type) }}</td>
                                <td class="px-3 py-3 text-[var(--text-secondary)]">{{ $field->is_required ? __('Yes') : __('No') }}</td>
                                <td class="px-3 py-3">
                                    <x-dynamic-fields.value-cell :field="$field" :value="$cellValue" :optional-values="true" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endif
