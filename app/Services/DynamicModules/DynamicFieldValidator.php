<?php

namespace App\Services\DynamicModules;

use App\Models\FieldDefinition;
use App\Models\Module;

class DynamicFieldValidator
{
    /**
     * @return array<string, list<string|\Illuminate\Validation\Rules\In>>
     */
    public function rulesFor(Module $module, string $prefix = ''): array
    {
        $module->loadMissing('fieldDefinitions');

        $rules = [];

        foreach ($module->fieldDefinitions as $field) {
            $key = $prefix !== ''
                ? $prefix.'.'.$field->field_name
                : $field->field_name;

            $rules[$key] = $this->rulesForField($field);
        }

        if ($prefix !== '' && $module->fieldDefinitions->isNotEmpty()) {
            $rules[$prefix] = ['required', 'array'];
        }

        return $rules;
    }

    /**
     * Record forms (legacy JSON modules): field values are optional on save even when marked required in schema.
     *
     * @return array<string, list<string|\Illuminate\Validation\Rules\In>>
     */
    public function rulesForServiceForm(Module $module, string $prefix = 'custom_fields'): array
    {
        $module->loadMissing('fieldDefinitions');

        $rules = [];

        foreach ($module->fieldDefinitions as $field) {
            $fieldRules = $this->rulesForField($field);
            $fieldRules[0] = 'nullable';
            $rules[$prefix.'.'.$field->field_name] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributesFor(Module $module, string $prefix = ''): array
    {
        $module->loadMissing('fieldDefinitions');

        $attributes = [];

        foreach ($module->fieldDefinitions as $field) {
            $key = $prefix !== ''
                ? $prefix.'.'.$field->field_name
                : $field->field_name;

            $attributes[$key] = $field->label;
        }

        return $attributes;
    }

    /**
     * @return list<string|\Illuminate\Validation\Rules\In>
     */
    private function rulesForField(FieldDefinition $field): array
    {
        $rules = [$field->is_required ? 'required' : 'nullable'];

        return array_merge($rules, match ($field->field_type) {
            FieldDefinition::TYPE_TEXT => ['string', 'max:255'],
            FieldDefinition::TYPE_TEXTAREA => ['string', 'max:65535'],
            FieldDefinition::TYPE_NUMBER => ['numeric'],
            FieldDefinition::TYPE_BOOLEAN => ['boolean'],
            FieldDefinition::TYPE_EMAIL => ['email', 'max:255'],
            FieldDefinition::TYPE_URL => ['url', 'max:2048'],
            FieldDefinition::TYPE_DATE => ['date'],
            FieldDefinition::TYPE_SELECT => [\Illuminate\Validation\Rule::in($field->selectOptions())],
            default => ['string', 'max:255'],
        });
    }
}
