<?php

namespace Tests;

use App\Support\TestingDatabaseGuard;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Cached config on production can bake in the live SQLite path and ignore phpunit.xml.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        $app->make('db')->purge('sqlite');

        RefreshDatabaseState::$migrated = false;
        RefreshDatabaseState::$inMemoryConnections = [];
        RefreshDatabaseState::$lazilyRefreshed = false;

        TestingDatabaseGuard::assertIsolated();

        if (is_file(Application::inferBasePath().'/bootstrap/cache/config.php')) {
            $app->make(Kernel::class)->call('migrate:fresh', ['--force' => true]);
            RefreshDatabaseState::$migrated = true;
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        TestingDatabaseGuard::assertIsolated();
    }
}
