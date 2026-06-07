<?php

namespace App\Services\Seo;

use Illuminate\Support\Facades\File;

/**
 * Validates no hardcoded locality logic in core engine PHP files.
 */
class DatabaseFirstComplianceValidator
{
    private const FORBIDDEN_PATTERNS = [
        'Arekere',
        "'Bangalore'",
        '"Bangalore"',
        'Bengaluru / Arekere',
    ];

    /**
     * @return array{compliant: bool, violations: list<array{file: string, pattern: string}>}
     */
    public function scanAppServices(): array
    {
        $violations = [];
        $paths = [
            base_path('app/Services'),
            base_path('app/Jobs'),
            base_path('app/Console/Commands'),
        ];

        foreach ($paths as $root) {
            if (! is_dir($root)) {
                continue;
            }

            foreach (File::allFiles($root) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                if (str_ends_with($file->getPathname(), 'DatabaseFirstComplianceValidator.php')) {
                    continue;
                }
                $contents = File::get($file->getPathname());
                foreach (self::FORBIDDEN_PATTERNS as $pattern) {
                    if (str_contains($contents, $pattern)) {
                        $violations[] = [
                            'file' => str_replace(base_path().'/', '', $file->getPathname()),
                            'pattern' => $pattern,
                        ];
                    }
                }
            }
        }

        return [
            'compliant' => $violations === [],
            'violations' => $violations,
        ];
    }
}
