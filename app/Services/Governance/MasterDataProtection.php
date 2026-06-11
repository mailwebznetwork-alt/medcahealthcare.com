<?php

namespace App\Services\Governance;

final class MasterDataProtection
{
    public function isEnabled(): bool
    {
        return (bool) config('master_data_protection.enabled', false);
    }

    /**
     * @param  'ui'|'import'|'seeder'|'populate'|'growth'|'system'  $source
     */
    public function allowsWrite(string $source): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        return in_array($source, ['ui', 'import'], true);
    }

    /**
     * Single-entity pincode imports (CSV upload, legacy path).
     * Workbook imports always upsert — see pincodeWorkbookUpsertEnabled().
     */
    public function pincodeUpsertEnabled(bool $forceUpsert = false): bool
    {
        if ($forceUpsert) {
            return true;
        }

        $configured = config('master_data_protection.pincode_upsert_default');
        if ($configured !== null) {
            return filter_var($configured, FILTER_VALIDATE_BOOLEAN);
        }

        if (app()->environment('local')) {
            return true;
        }

        if (app()->environment('staging')) {
            return (bool) config('master_data_protection.pincode_upsert_in_staging', false);
        }

        return ! app()->isProduction();
    }

    /**
     * Master pincodes.xlsx workbook imports (Operations UI / CLI).
     * Always update existing rows — documented in MASTER-XLS-GUIDE.md.
     */
    public function pincodeWorkbookUpsertEnabled(): bool
    {
        return true;
    }
}
