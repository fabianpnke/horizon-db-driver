<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enable the Database Driver
    |--------------------------------------------------------------------------
    |
    | When enabled, Horizon's Redis-backed lock, repositories, and command
    | queue are swapped for database-backed implementations, and a
    | "database" queue connector is registered. Disable this to fall
    | back to Horizon's default Redis driver without removing the package.
    |
    */

    'enabled' => env('HORIZON_DB_DRIVER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection Horizon should use to store its meta
    | information when the database driver is enabled. If null, the
    | application's default connection is used.
    |
    */

    'connection' => env('HORIZON_DB_DRIVER_CONNECTION'),

];
