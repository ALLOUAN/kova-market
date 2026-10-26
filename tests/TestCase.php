<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Tests empty and rebuild their database: they must only ever run on the in-memory SQLite of phpunit.xml. A
     * configuration cache ("php artisan optimize" or "config:cache" on a working copy) would make them read the
     * .env instead and wipe the real database, so they stop before touching anything.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if ($app->configurationIsCached() || $connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException("Tests refused: they would run on the \"{$connection}\" database \"{$database}\" instead of the in-memory SQLite. Run \"php artisan optimize:clear\" first.");
        }

        return $app;
    }
}
