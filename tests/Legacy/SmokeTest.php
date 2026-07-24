<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Tests\Legacy;

use HorizonDbDriver\HorizonDbDriver\Connectors\DatabaseConnector;
use HorizonDbDriver\HorizonDbDriver\DatabaseHorizonCommandQueue;
use HorizonDbDriver\HorizonDbDriver\DatabaseLock;
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
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Contracts\HorizonCommandQueue;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\ProcessRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\Lock;

/**
 * A lighter-weight PHPUnit smoke suite for the PHP 8.0/8.1 CI legs, where Pest
 * itself has no installable release (v1 caps at PHP 8.0, v2+ requires PHP 8.2+).
 * This proves the package boots and wires its core bindings correctly; the full
 * Pest suite in tests/Feature and tests/Unit covers behavior in depth on PHP 8.2+.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_merges_the_package_config_with_enabled_defaulting_to_true(): void
    {
        $this->assertTrue(config('horizon-db-driver.enabled'));
        $this->assertNull(config('horizon-db-driver.connection'));
    }

    public function test_it_rebinds_every_driver_swappable_singleton_to_the_database_implementation(): void
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

    public function test_it_pushes_a_job_onto_the_database_queue_and_records_it_as_pending(): void
    {
        $this->app->make('queue')->connection('database')->push('SomeClass@handle', ['foo' => 'bar']);

        $row = DB::table('horizon_jobs')->first();

        $this->assertNotNull($row);
        $this->assertSame('pending', $row->status);
    }

    public function test_it_acquires_and_releases_a_lock(): void
    {
        $lock = $this->app->make(Lock::class);

        $this->assertTrue($lock->get('horizon:legacy-smoke-lock'));
        $this->assertTrue($lock->exists('horizon:legacy-smoke-lock'));

        $lock->release('horizon:legacy-smoke-lock');

        $this->assertFalse($lock->exists('horizon:legacy-smoke-lock'));
    }
}
