<?php

namespace App\Services\DynamicModules;

use App\Models\FieldDefinition;
use App\Models\Module;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use stdClass;

class LegacyCustomFieldService
{
    public function __construct(
        private readonly DynamicFieldValidator $validator,
    ) {}

    /**
     * @return array<string, list<string|\Illuminate\Validation\Rules\In>>
     */
    public function prefixedRules(Module $module): array
    {
        $module->loadMissing('fieldDefinitions');

        $rules = [];

        foreach ($this->validator->rulesFor($module) as $fieldName => $fieldRules) {
            $rules['custom_fields.'.$fieldName] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedValues(Request $request, Module $module): array
    {
        $module->loadMissing('fieldDefinitions');

        if ($module->fieldDefinitions->isEmpty()) {
            return [];
        }

        $validated = $request->validate($this->prefixedRules($module));

        return $this->normalizePayload($module, $validated['custom_fields'] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedValuesForServiceForm(Request $request, Module $module): array
    {
        $module->loadMissing('fieldDefinitions');

        if ($module->fieldDefinitions->isEmpty()) {
            return [];
        }

        $validated = $request->validate(
            $this->validator->rulesForServiceForm($module),
            [],
            $this->validator->attributesFor($module, 'custom_fields')
        );

        return $this->normalizePayload($module, $validated['custom_fields'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizePayload(Module $module, array $payload): array
    {
        $allowed = $module->fieldDefinitions->pluck('field_name')->all();
        $payload = Arr::only($payload, $allowed);

        foreach ($module->fieldDefinitions as $field) {
            if ($field->field_type !== FieldDefinition::TYPE_BOOLEAN) {
                continue;
            }

            $payload[$field->field_name] = filter_var(
                $payload[$field->field_name] ?? false,
                FILTER_VALIDATE_BOOLEAN
            );
        }

        return $payload;
    }

    public function valuesObject(?Model $record): stdClass
    {
        if ($record === null) {
            return new stdClass;
        }

        $values = $record->getAttribute('custom_fields');

        if (! is_array($values)) {
            return new stdClass;
        }

        return (object) $values;
    }

    public function persistOnModel(Model $record, Module $module, array $values): void
    {
        if ($module->usesColumnStorage()) {
            foreach ($values as $column => $value) {
                if ($module->fieldDefinitions->contains('field_name', $column)) {
                    $record->setAttribute($column, $value);
                }
            }

            $record->save();

            return;
        }

        $record->forceFill([
            'custom_fields' => array_merge(
                is_array($record->getAttribute('custom_fields')) ? $record->getAttribute('custom_fields') : [],
                $values
            ),
        ])->save();
    }
}
