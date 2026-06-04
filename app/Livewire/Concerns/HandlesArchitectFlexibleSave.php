<?php

namespace App\Livewire\Concerns;

use App\Models\User;
use App\Support\ArchitectSaveBypass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;

trait HandlesArchitectFlexibleSave
{
    public bool $architectOverwriteApproved = false;

    public bool $architectIncompleteSaveApproved = false;

    public function approveArchitectOverwrite(): void
    {
        $this->architectOverwriteApproved = true;
    }

    public function approveArchitectIncompleteSave(): void
    {
        $this->architectIncompleteSaveApproved = true;
    }

    protected function resetArchitectSaveFlags(): void
    {
        $this->architectOverwriteApproved = false;
        $this->architectIncompleteSaveApproved = false;
    }

    protected function architectSaveBypassEligible(): bool
    {
        $user = Auth::user();

        return $user instanceof User && ArchitectSaveBypass::eligible($user);
    }

    /**
     * @param  array<string, mixed>  $rules
     * @param  list<string>  $requiredProperties
     */
    protected function validateArchitectForm(array $rules, array $requiredProperties = []): bool
    {
        if ($this->architectSaveBypassEligible()) {
            if (! $this->architectIncompleteSaveApproved) {
                foreach ($requiredProperties as $property) {
                    if (trim((string) ($this->{$property} ?? '')) === '') {
                        $this->addError(
                            'architect_save',
                            __('Some required fields are empty. Use “Save anyway” to continue.')
                        );

                        return false;
                    }
                }
            } else {
                $rules = ArchitectSaveBypass::relaxRequiredRules($rules);
            }
        }

        $rules = ArchitectSaveBypass::stripUniqueRules($rules);

        $this->validate($rules);

        return true;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function assertArchitectUniqueAvailable(
        string $modelClass,
        string $column,
        string $value,
        ?int $ignoreId,
        string $labelAttribute = 'block_name',
        ?string $errorField = null,
    ): bool {
        $conflict = ArchitectSaveBypass::findConflict($modelClass, $column, $value, $ignoreId);
        if ($conflict === null) {
            return true;
        }

        if ($this->architectOverwriteApproved) {
            ArchitectSaveBypass::releaseUniqueValue($conflict, $column);
            $this->architectOverwriteApproved = false;

            return true;
        }

        $label = (string) ($conflict->getAttribute($labelAttribute) ?? $conflict->getKey());
        $field = $errorField ?? $column;

        $this->addError(
            $field,
            __('Already used by “:label” (:value). Use “Overwrite & save” to reassign their :column and continue.', [
                'label' => $label,
                'value' => $conflict->getAttribute($column),
                'column' => $column,
            ])
        );

        return false;
    }

    /**
     * @param  array<int, mixed>  $rules
     * @return array<int, mixed>
     */
    protected function withoutRuleUnique(array $rules): array
    {
        return array_values(array_filter(
            $rules,
            static fn (mixed $r): bool => ! $r instanceof Unique
        ));
    }
}
