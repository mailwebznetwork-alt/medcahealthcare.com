<?php

namespace App\Services\Import;

/**
 * Suppresses per-row observer orchestration during bulk imports.
 * Post-import artisan sync commands run once after commit instead.
 */
final class ImportSideEffectsGate
{
    private int $depth = 0;

    public function suppressed(): bool
    {
        return $this->depth > 0;
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function run(callable $callback): mixed
    {
        $this->depth++;

        try {
            return $callback();
        } finally {
            $this->depth--;
        }
    }
}
