<?php

namespace App\Services\Import;

use App\Services\Import\Contracts\EntityImporter;
use InvalidArgumentException;

/**
 * Central registry for database-first bulk imports.
 */
class ImportRegistry
{
    /** @var array<string, class-string<EntityImporter>> */
    private array $importers = [];

    public function register(string $entityKey, string $importerClass): void
    {
        $this->importers[$entityKey] = $importerClass;
    }

    public function resolve(string $entityKey): EntityImporter
    {
        $class = $this->importers[$entityKey] ?? null;
        if ($class === null || ! class_exists($class)) {
            throw new InvalidArgumentException("No importer registered for [{$entityKey}].");
        }

        return app($class);
    }

    /**
     * @return list<string>
     */
    public function registeredEntities(): array
    {
        return array_keys($this->importers);
    }

    /**
     * @return array<string, array{importer: class-string<EntityImporter>, status: string}>
     */
    public function readinessMatrix(): array
    {
        $configured = config('import_registry.entities', []);
        $matrix = [];

        foreach ($configured as $key => $meta) {
            $matrix[$key] = [
                'importer' => $this->importers[$key] ?? ($meta['importer'] ?? ''),
                'status' => isset($this->importers[$key]) ? 'implemented' : ($meta['status'] ?? 'planned'),
                'formats' => $meta['formats'] ?? ['csv', 'xlsx'],
            ];
        }

        return $matrix;
    }
}
