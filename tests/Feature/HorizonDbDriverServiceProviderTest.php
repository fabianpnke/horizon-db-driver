<?php

declare(strict_types=1);

use HorizonDbDriver\HorizonDbDriver\Connectors\DatabaseConnector;
use HorizonDbDriver\HorizonDbDriver\DatabaseHorizonCommandQueue;
use HorizonDbDriver\HorizonDbDriver\DatabaseLock;
use HorizonDbDriver\HorizonDbDriver\HorizonDbDriverServiceProvider;
use HorizonDbDriver\HorizonDbDriver\Repositories\DatabaseJobRepository;
use HorizonDbDriver\HorizonDbDriver\Repositories\DatabaseMasterSupervisorRepository;
use HorizonDbDriver\HorizonDbDriver\Repositories\DatabaseMetricsRepository;
use HorizonDbDriver\HorizonDbDriver\Repositories\DatabaseProcessRepository;
use HorizonDbDriver\HorizonDbDriver\Repositories\DatabaseSupervisorRepository;
use HorizonDbDriver\HorizonDbDriver\Repositories\DatabaseTagRepository;
use HorizonDbDriver\HorizonDbDriver\Repositories\DatabaseWorkloadRepository;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Contracts\HorizonCommandQueue;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\ProcessRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\Lock;

it('merges the package config with enabled defaulting to true', function () {
    expect(config('horizon-db-driver.enabled'))->toBeTrue();
    expect(config('horizon-db-driver.connection'))->toBeNull();
});

it('publishes the migrations under the expected tags', function () {
    $paths = ServiceProvider::pathsToPublish(HorizonDbDriverServiceProvider::class, 'horizon-db-driver-migrations');

    expect($paths)->not->toBeEmpty();
});

it('rebinds every driver-swappable singleton to the database implementation when enabled', function () {
    expect(app(Lock::class))->toBeInstanceOf(DatabaseLock::class);
    expect(app(HorizonCommandQueue::class))->toBeInstanceOf(DatabaseHorizonCommandQueue::class);
    expect(app(JobRepository::class))->toBeInstanceOf(DatabaseJobRepository::class);
    expect(app(MasterSupervisorRepository::class))->toBeInstanceOf(DatabaseMasterSupervisorRepository::class);
    expect(app(MetricsRepository::class))->toBeInstanceOf(DatabaseMetricsRepository::class);
    expect(app(ProcessRepository::class))->toBeInstanceOf(DatabaseProcessRepository::class);
    expect(app(SupervisorRepository::class))->toBeInstanceOf(DatabaseSupervisorRepository::class);
    expect(app(TagRepository::class))->toBeInstanceOf(DatabaseTagRepository::class);
    expect(app(WorkloadRepository::class))->toBeInstanceOf(DatabaseWorkloadRepository::class);
});

it('registers the database queue connector', function () {
    $manager = app(QueueManager::class);

    $connectors = (fn () => $this->connectors)->call($manager);

    expect($connectors)->toHaveKey('database');
    expect($connectors['database']())->toBeInstanceOf(DatabaseConnector::class);
});
