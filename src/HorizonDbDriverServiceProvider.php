<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver;

use HorizonDbDriver\HorizonDbDriver\Connectors\DatabaseConnector;
use HorizonDbDriver\HorizonDbDriver\Listeners\MarshalDatabaseFailedEvent;
use HorizonDbDriver\HorizonDbDriver\Listeners\TrimTags;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Contracts\HorizonCommandQueue;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\ProcessRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\Events\MasterSupervisorLooped;
use Laravel\Horizon\Lock;

class HorizonDbDriverServiceProvider extends ServiceProvider
{
    /**
     * The service bindings that swap Horizon's Redis-backed implementations
     * for this package's database-backed implementations.
     *
     * @var array<class-string, class-string>
     */
    protected array $databaseServiceBindings = [
        Lock::class => DatabaseLock::class,
        HorizonCommandQueue::class => DatabaseHorizonCommandQueue::class,
        JobRepository::class => Repositories\DatabaseJobRepository::class,
        MasterSupervisorRepository::class => Repositories\DatabaseMasterSupervisorRepository::class,
        MetricsRepository::class => Repositories\DatabaseMetricsRepository::class,
        ProcessRepository::class => Repositories\DatabaseProcessRepository::class,
        SupervisorRepository::class => Repositories\DatabaseSupervisorRepository::class,
        TagRepository::class => Repositories\DatabaseTagRepository::class,
        WorkloadRepository::class => Repositories\DatabaseWorkloadRepository::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/horizon-db-driver.php', 'horizon-db-driver');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], ['horizon-db-driver', 'horizon-db-driver-migrations']);

            $this->publishes([
                __DIR__.'/../config/horizon-db-driver.php' => config_path('horizon-db-driver.php'),
            ], ['horizon-db-driver', 'horizon-db-driver-config']);
        }

        if (! config('horizon-db-driver.enabled')) {
            return;
        }

        foreach ($this->databaseServiceBindings as $abstract => $concrete) {
            $this->app->singleton($abstract, $concrete);
        }

        $this->app->afterResolving(QueueManager::class, function (QueueManager $manager): void {
            $manager->addConnector('database', function (): DatabaseConnector {
                return new DatabaseConnector($this->app->make(ConnectionResolverInterface::class));
            });
        });

        Event::listen(MasterSupervisorLooped::class, TrimTags::class);
        Event::listen(JobFailed::class, MarshalDatabaseFailedEvent::class);
    }
}
