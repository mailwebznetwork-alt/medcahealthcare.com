@props([
    'field',
    'value',
    'inputId' => null,
    'optionalValues' => false,
])

@php
    /** @var \App\Models\FieldDefinition $field */
    $inputName = 'custom_fields['.$field->field_name.']';
    $errorKey = 'custom_fields.'.$field->field_name;
    $inputId = $inputId ?? 'custom_'.$field->field_name;
    $requiredAttr = ($optionalValues || ! $field->is_required) ? '' : 'required';
@endphp

@if ($field->field_type === \App\Models\FieldDefinition::TYPE_TEXTAREA)
    <textarea id="{{ $inputId }}" name="{{ $inputName }}" rows="2" class="block w-full min-w-[10rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner" {{ $requiredAttr }}>{{ $value }}</textarea>
@elseif ($field->field_type === \App\Models\FieldDefinition::TYPE_BOOLEAN)
    <div class="flex items-center gap-2">
        <input type="hidden" name="{{ $inputName }}" value="0">
        <input id="{{ $inputId }}" name="{{ $inputName }}" type="checkbox" value="1" class="h-4 w-4 rounded border-[rgba(255,255,255,0.12)] bg-[rgba(28,22,18,0.75)] text-mom-gold focus:ring-1 focus:ring-[rgba(197,160,89,0.35)]" @checked(filter_var($value, FILTER_VALIDATE_BOOLEAN)) />
        <span class="text-xs text-[var(--text-muted)]">{{ __('Enabled') }}</span>
    </div>
@elseif ($field->field_type === \App\Models\FieldDefinition::TYPE_SELECT)
    <select id="{{ $inputId }}" name="{{ $inputName }}" class="block w-full min-w-[10rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner" {{ $requiredAttr }}>
        <option value="">{{ __('Select…') }}</option>
        @foreach ($field->selectOptions() as $option)
            <option value="{{ $option }}" @selected((string) $value === (string) $option)>{{ $option }}</option>
        @endforeach
    </select>
@elseif ($field->field_type === \App\Models\FieldDefinition::TYPE_NUMBER)
    <input id="{{ $inputId }}" name="{{ $inputName }}" type="number" step="any" class="block w-full min-w-[10rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner" value="{{ $value }}" {{ $requiredAttr }} />
@elseif ($field->field_type === \App\Models\FieldDefinition::TYPE_DATE)
    <input id="{{ $inputId }}" name="{{ $inputName }}" type="date" class="block w-full min-w-[10rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner" value="{{ $value }}" {{ $requiredAttr }} />
@elseif ($field->field_type === \App\Models\FieldDefinition::TYPE_EMAIL)
    <input id="{{ $inputId }}" name="{{ $inputName }}" type="email" class="block w-full min-w-[10rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner" value="{{ $value }}" {{ $requiredAttr }} />
@elseif ($field->field_type === \App\Models\FieldDefinition::TYPE_URL)
    <input id="{{ $inputId }}" name="{{ $inputName }}" type="url" class="block w-full min-w-[10rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner" value="{{ $value }}" {{ $requiredAttr }} />
@else
    <input id="{{ $inputId }}" name="{{ $inputName }}" type="text" class="block w-full min-w-[10rem] rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)] shadow-mom-inner" value="{{ $value }}" {{ $requiredAttr }} />
@endif
<x-input-error class="mt-1" :messages="$errors->get($errorKey)" variant="mom" />
