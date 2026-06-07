<?php

namespace App\Services\Import\Contracts;

/**
 * Staged import contract: validate → preview → commit.
 */
interface EntityImporter
{
    public function entityKey(): string;

    /**
     * @param  string|resource|\Illuminate\Http\UploadedFile  $source
     * @return array{valid: bool, errors: list<string>, rows: list<array<string, mixed>>, total_data_rows: int}
     */
    public function preview($source, int $limit = 25): array;

    /**
     * @param  string|resource|\Illuminate\Http\UploadedFile  $source
     * @return array{created: int, updated: int, skipped: int, failed: int, errors: list<string>}
     */
    public function import($source): array;
}
