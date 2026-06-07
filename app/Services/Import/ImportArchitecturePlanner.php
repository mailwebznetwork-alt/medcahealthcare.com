<?php

namespace App\Services\Import;

/**
 * XLS import completion architecture — templates, validation, workflow (planning layer).
 */
class ImportArchitecturePlanner
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function entityPlans(): array
    {
        return config('import_registry.entity_plans', []);
    }

    /**
     * @return array{stages: list<string>, rollback: array<string, mixed>}
     */
    public function workflow(): array
    {
        return [
            'stages' => ['upload', 'validate', 'preview', 'approve', 'commit', 'audit'],
            'rollback' => [
                'strategy' => 'transaction_per_batch',
                'import_log_table' => 'import_batches',
                'revert_by_batch_id' => true,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function readinessReport(): array
    {
        $registry = app(ImportRegistry::class);

        return [
            'registered_importers' => $registry->registeredEntities(),
            'entity_matrix' => $registry->readinessMatrix(),
            'entity_plans' => $this->entityPlans(),
            'workflow' => $this->workflow(),
        ];
    }
}
