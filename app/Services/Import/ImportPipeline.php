<?php

namespace App\Services\Import;

/**
 * Orchestrates validate → preview → commit → audit → post-sync for registered importers.
 */
class ImportPipeline
{
    public function __construct(
        private readonly ImportRegistry $registry,
        private readonly ImportBatchRecorder $recorder,
        private readonly ImportPostSyncService $postSync,
    ) {}

    /**
     * @return array{valid: bool, errors: list<string>, rows: list<array<string, mixed>>, total_data_rows: int}
     */
    public function preview(string $entityKey, mixed $source, int $limit = 25): array
    {
        $importer = $this->registry->resolve($entityKey);

        if (is_array($source) && isset($source['headers']) && $importer instanceof AbstractSpreadsheetImporter) {
            return $importer->previewParsed($source, $limit);
        }

        return $importer->preview($source, $limit);
    }

    /**
     * @return array{
     *     created: int, updated: int, skipped: int, failed: int,
     *     errors: list<string>, batch_id: int|null, post_sync: list<string>
     * }
     */
    /**
     * @param  array{upsert_pincodes?: bool}  $options
     */
    public function commit(string $entityKey, mixed $source, ?int $userId = null, ?string $filename = null, bool $runPostSync = true, array $options = []): array
    {
        $this->recorder->start($entityKey, $userId, $filename);

        $importer = $this->registry->resolve($entityKey);
        if ($entityKey === 'pincodes' && ($options['upsert_pincodes'] ?? false) && $importer instanceof PinCodeSpreadsheetImporter) {
            $importer->withUpsert(true);
        }

        if (is_array($source) && isset($source['headers']) && $importer instanceof AbstractSpreadsheetImporter) {
            $result = $importer->importParsed($source);
        } else {
            $result = $importer->import($source);
        }

        $batch = $this->recorder->finish($result);

        $postSync = [];
        if ($runPostSync && ($result['created'] > 0 || $result['updated'] > 0)) {
            $postSync = $this->postSync->syncForEntity($entityKey);
        }

        return array_merge($result, [
            'batch_id' => $batch->id,
            'post_sync' => $postSync,
        ]);
    }

    /**
     * @param  list<string>  $entityKeys
     * @return list<string>
     */
    public function postSyncForEntities(array $entityKeys): array
    {
        $order = config('import_registry.import_order', []);
        $commands = [];

        foreach ($order as $entityKey) {
            if (! in_array($entityKey, $entityKeys, true)) {
                continue;
            }
            $commands = array_merge($commands, $this->postSync->syncForEntity($entityKey));
        }

        return array_values(array_unique($commands));
    }
}
