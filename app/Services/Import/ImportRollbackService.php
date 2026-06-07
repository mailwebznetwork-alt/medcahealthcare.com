<?php

namespace App\Services\Import;

use App\Models\ImportBatch;
use App\Models\ImportBatchEntry;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServicePincode;
use App\Models\SubService;
use Illuminate\Support\Facades\DB;

class ImportRollbackService
{
    /**
     * @return array{success: bool, reverted: int, errors: list<string>}
     */
    public function rollback(int $batchId): array
    {
        $batch = ImportBatch::query()->with('entries')->find($batchId);
        if ($batch === null) {
            return ['success' => false, 'reverted' => 0, 'errors' => [__('Import batch not found.')]];
        }

        if (! $batch->isRollbackable()) {
            return ['success' => false, 'reverted' => 0, 'errors' => [__('Batch cannot be rolled back.')]];
        }

        $reverted = 0;
        $errors = [];

        DB::transaction(function () use ($batch, &$reverted, &$errors): void {
            foreach ($batch->entries()->orderByDesc('id')->get() as $entry) {
                try {
                    if ($this->revertEntry($entry)) {
                        $reverted++;
                    }
                } catch (\Throwable $e) {
                    $errors[] = "Entry {$entry->id}: {$e->getMessage()}";
                }
            }

            $batch->update([
                'status' => 'rolled_back',
                'rolled_back_at' => now(),
            ]);
        });

        return ['success' => $errors === [], 'reverted' => $reverted, 'errors' => $errors];
    }

    private function revertEntry(ImportBatchEntry $entry): bool
    {
        if ($entry->entity_id === null) {
            return false;
        }

        return match ($entry->action) {
            'created' => $this->deleteEntity($entry->entity_type, $entry->entity_id),
            'updated' => $this->restoreEntity($entry->entity_type, $entry->entity_id, $entry->previous_state),
            default => false,
        };
    }

    private function deleteEntity(string $type, int $id): bool
    {
        $model = $this->resolveModel($type);
        if ($model === null) {
            return false;
        }

        return (bool) $model::query()->whereKey($id)->delete();
    }

    /**
     * @param  array<string, mixed>|null  $state
     */
    private function restoreEntity(string $type, int $id, ?array $state): bool
    {
        if ($state === null || $state === []) {
            return false;
        }

        $model = $this->resolveModel($type);
        if ($model === null) {
            return false;
        }

        unset($state['id'], $state['created_at'], $state['updated_at']);

        return (bool) $model::query()->whereKey($id)->update($state);
    }

    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model>|null
     */
    private function resolveModel(string $type): ?string
    {
        return match ($type) {
            'service_category' => ServiceCategory::class,
            'service' => Service::class,
            'sub_service' => SubService::class,
            'pin_code' => PinCode::class,
            'service_pincode' => ServicePincode::class,
            default => null,
        };
    }
}
