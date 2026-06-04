@props([
    'module',
    'values',
])

@php
    /** @var \App\Models\Module $module */
    /** @var object $values */
    $module->loadMissing('fieldDefinitions');
@endphp

@if ($module->fieldDefinitions->isNotEmpty())
    <section class="mom-card p-6">
        <h3 class="mom-section-title">{{ __('Custom fields') }}</h3>
        <p class="mom-subtext mt-2 mb-6">{{ __('Additional attributes defined in Module Builder for :module.', ['module' => $module->name]) }}</p>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            @foreach ($module->fieldDefinitions as $field)
                @php
                    $name = 'custom_fields.'.$field->field_name;
                    $inputName = 'custom_fields['.$field->field_name.']';
                    $value = old('custom_fields.'.$field->field_name, $values->{$field->field_name} ?? null);
                    $requiredAttr = '';
                @endphp
                <div @class(['md:col-span-2' => $field->field_type === \App\Models\FieldDefinition::TYPE_TEXTAREA])>
                    <x-input-label :for="'custom_'.$field->field_name" :value="$field->label" variant="mom" />
                    @if ($field->field_type === \App\Models\FieldDefinition::TYPE_TEXTAREA)
                        <textarea id="custom_{{ $field->field_name }}" name="{{ $inputName }}" rows="4" class="mt-2 block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner" {{ $requiredAttr }}>{{ $value }}</textarea>
                    @elseif ($field->field_type === \App\Models\FieldDefinition::TYPE_BOOLEAN)
                        <div class="mt-2 flex items-center gap-3">
                            <input type="hidden" name="{{ $inputName }}" value="0">
                            <input id="custom_{{ $field->field_name }}" name="{{ $inputName }}" type="checkbox" value="1" class="h-4 w-4 rounded border-[rgba(255,255,255,0.12)] bg-[rgba(28,22,18,0.75)] text-mom-gold focus:ring-1 focus:ring-[rgba(197,160,89,0.35)]" @checked(filter_var($value, FILTER_VALIDATE_BOOLEAN)) />
                            <span class="text-sm text-[var(--text-secondary)]">{{ __('Enabled') }}</span>
                        </div>
                    @elseif ($field->field_type === \App\Models\FieldDefinition::TYPE_SELECT)
                        <select id="custom_{{ $field->field_name }}" name="{{ $inputName }}" class="mt-2 block w-full rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner" {{ $requiredAttr }}>
                            <option value="">{{ __('Select…') }}</option>
                            @foreach ($field->selectOptions() as $option)
                                <option value="{{ $option }}" @selected((string) $value === (string) $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    @elseif ($field->field_type === \App\Models\FieldDefinition::TYPE_NUMBER)
                        <x-text-input :id="'custom_'.$field->field_name" :name="$inputName" type="number" step="any" class="mt-2 block w-full" :value="$value" variant="mom" />
                    @elseif ($field->field_type === \App\Models\FieldDefinition::TYPE_DATE)
                        <x-text-input :id="'custom_'.$field->field_name" :name="$inputName" type="date" class="mt-2 block w-full" :value="$value" variant="mom" />
                    @elseif ($field->field_type === \App\Models\FieldDefinition::TYPE_EMAIL)
                        <x-text-input :id="'custom_'.$field->field_name" :name="$inputName" type="email" class="mt-2 block w-full" :value="$value" variant="mom" />
                    @elseif ($field->field_type === \App\Models\FieldDefinition::TYPE_URL)
                        <x-text-input :id="'custom_'.$field->field_name" :name="$inputName" type="url" class="mt-2 block w-full" :value="$value" variant="mom" />
                    @else
                        <x-text-input :id="'custom_'.$field->field_name" :name="$inputName" type="text" class="mt-2 block w-full" :value="$value" variant="mom" />
                    @endif
                    <x-input-error class="mt-2" :messages="$errors->get($name)" variant="mom" />
                </div>
            @endforeach
        </div>
    </section>
@endif
