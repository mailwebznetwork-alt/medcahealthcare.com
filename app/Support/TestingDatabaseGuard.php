<?php

namespace App\Support;

use RuntimeException;

/**
 * Prevents PHPUnit/Pest from mutating the production SQLite file when config is cached.
 */
class TestingDatabaseGuard
{
    public static function liveSQLitePath(): string
    {
        return database_path('database.sqlite');
    }

    public static function isPointingAtLiveDatabase(): bool
    {
        if (config('database.default') !== 'sqlite') {
            return false;
        }

        $database = (string) config('database.connections.sqlite.database');

        if ($database === ':memory:') {
            return false;
        }

        $configured = realpath($database) ?: $database;
        $live = realpath(static::liveSQLitePath()) ?: static::liveSQLitePath();

        return $configured === $live;
    }

    public static function assertIsolated(): void
    {
        if (! app()->environment('testing')) {
            return;
        }

        if (static::isPointingAtLiveDatabase()) {
            throw new RuntimeException(
                'Refusing to run tests against the live SQLite database at '.static::liveSQLitePath().'. '.
                'Run `composer test` (clears config cache) or `php artisan config:clear` before `php artisan test`. '.
                'Never run the full test suite on the production server.'
            );
        }
    }
}
