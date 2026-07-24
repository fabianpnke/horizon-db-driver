<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Tests;

use HorizonDbDriver\HorizonDbDriver\HorizonDbDriverServiceProvider;
use Laravel\Horizon\HorizonServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use WithWorkbench;

    protected function getPackageProviders($app): array
    {
        return [
            HorizonServiceProvider::class,
            HorizonDbDriverServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        tap($app['config'], function ($config) {
            $config->set('database.default', 'testbench');
            $config->set('database.connections.testbench', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);

            $config->set('queue.connections.database', [
                'driver' => 'database',
                'table' => 'jobs',
                'queue' => 'default',
                'retry_after' => 90,
            ]);
        });
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function disableHorizonDbDriver($app): void
    {
        $app['config']->set('horizon-db-driver.enabled', false);
    }
}
