<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver;

use HorizonDbDriver\HorizonDbDriver\Console\Commands\HorizonDbDriverCommand;
use Illuminate\Support\ServiceProvider;

class HorizonDbDriverServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/horizon-db-driver.php', 'horizon-db-driver');

        $this->app->singleton(HorizonDbDriver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/horizon-db-driver.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'horizon-db-driver');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'horizon-db-driver');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/horizon-db-driver.php' => config_path('horizon-db-driver.php'),
        ], ['horizon-db-driver', 'horizon-db-driver-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/horizon-db-driver'),
        ], ['horizon-db-driver', 'horizon-db-driver-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/horizon-db-driver'),
        ], ['horizon-db-driver', 'horizon-db-driver-lang']);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/horizon-db-driver'),
        ], ['horizon-db-driver', 'horizon-db-driver-assets']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['horizon-db-driver', 'horizon-db-driver-migrations']);

        $this->commands([
            HorizonDbDriverCommand::class,
        ]);
    }
}
