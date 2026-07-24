<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Tests\Feature;

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
use HorizonDbDriver\HorizonDbDriver\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
use Laravel\Horizon\Repositories\RedisJobRepository;
use Orchestra\Testbench\Attributes\DefineEnvironment;

class HorizonDbDriverServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_merges_the_package_config_with_enabled_defaulting_to_true(): void
    {
        $this->assertTrue(config('horizon-db-driver.enabled'));
        $this->assertNull(config('horizon-db-driver.connection'));
    }

    public function test_it_publishes_the_migrations_under_the_expected_tags(): void
    {
        $paths = ServiceProvider::pathsToPublish(HorizonDbDriverServiceProvider::class, 'horizon-db-driver-migrations');

        $this->assertNotEmpty($paths);
    }

    public function test_it_rebinds_every_driver_swappable_singleton_to_the_database_implementation_when_enabled(): void
    {
        $this->assertInstanceOf(DatabaseLock::class, $this->app->make(Lock::class));
        $this->assertInstanceOf(DatabaseHorizonCommandQueue::class, $this->app->make(HorizonCommandQueue::class));
        $this->assertInstanceOf(DatabaseJobRepository::class, $this->app->make(JobRepository::class));
        $this->assertInstanceOf(DatabaseMasterSupervisorRepository::class, $this->app->make(MasterSupervisorRepository::class));
        $this->assertInstanceOf(DatabaseMetricsRepository::class, $this->app->make(MetricsRepository::class));
        $this->assertInstanceOf(DatabaseProcessRepository::class, $this->app->make(ProcessRepository::class));
        $this->assertInstanceOf(DatabaseSupervisorRepository::class, $this->app->make(SupervisorRepository::class));
        $this->assertInstanceOf(DatabaseTagRepository::class, $this->app->make(TagRepository::class));
        $this->assertInstanceOf(DatabaseWorkloadRepository::class, $this->app->make(WorkloadRepository::class));
    }

    public function test_it_registers_the_database_queue_connector(): void
    {
        $manager = $this->app->make(QueueManager::class);

        $connectors = (fn () => $this->connectors)->call($manager);

        $this->assertArrayHasKey('database', $connectors);
        $this->assertInstanceOf(DatabaseConnector::class, $connectors['database']());
    }

    #[DefineEnvironment('disableHorizonDbDriver')]
    public function test_it_leaves_horizon_on_its_default_redis_bindings_when_disabled(): void
    {
        $this->assertInstanceOf(RedisJobRepository::class, $this->app->make(JobRepository::class));
    }
}
