<?php

namespace App\Http\Requests\Concerns;

use App\Models\User;
use App\Support\ArchitectSaveBypass;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;

trait InteractsWithArchitectSavePolicy
{
    public function architectOverwriteApproved(): bool
    {
        return $this->boolean('_architect_overwrite_approved');
    }

    public function architectIncompleteSaveApproved(): bool
    {
        return $this->boolean('_architect_incomplete_approved');
    }

    protected function architectSaveBypassEligible(): bool
    {
        $user = $this->user();

        return $user instanceof User && ArchitectSaveBypass::eligible($user);
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function architectPrepareRules(array $rules, array $requiredKeys = []): array
    {
        $rules = ArchitectSaveBypass::stripUniqueRules($rules);

        if ($this->architectSaveBypassEligible() && $this->architectIncompleteSaveApproved()) {
            return ArchitectSaveBypass::relaxRequiredRules($rules);
        }

        if ($this->architectSaveBypassEligible() && ! $this->architectIncompleteSaveApproved()) {
            foreach ($requiredKeys as $key) {
                if (trim((string) $this->input($key, '')) === '') {
                    // Let withValidator add the warning; keep required rules for first pass.
                }
            }
        }

        return $rules;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function architectAssertUnique(
        Validator $validator,
        string $modelClass,
        string $column,
        mixed $value,
        ?int $exceptId = null,
        string $labelAttribute = 'title',
    ): void {
        $conflict = ArchitectSaveBypass::findConflict($modelClass, $column, $value, $exceptId);
        if ($conflict === null) {
            return;
        }

        if ($this->architectOverwriteApproved()) {
            ArchitectSaveBypass::releaseUniqueValue($conflict, $column);

            return;
        }

        $validator->errors()->add(
            $column,
            __('Already used by “:label” (:value). Check “Overwrite & save” and submit again.', [
                'label' => (string) ($conflict->getAttribute($labelAttribute) ?? $conflict->getKey()),
                'value' => $conflict->getAttribute($column),
            ])
        );
    }

    protected function architectAssertIncompleteAcknowledged(Validator $validator, array $requiredKeys): void
    {
        if (! $this->architectSaveBypassEligible() || $this->architectIncompleteSaveApproved()) {
            return;
        }

        foreach ($requiredKeys as $key) {
            if (trim((string) $this->input($key, '')) === '') {
                $validator->errors()->add(
                    'architect_save',
                    __('Some required fields are empty. Check “Save anyway” and submit again.')
                );

                return;
            }
        }
    }
}
