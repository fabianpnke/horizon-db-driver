<div align="center">
    <h1>Horizon Db Driver</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/fabianpnke/horizon-db-driver"><img src="https://img.shields.io/packagist/v/fabianpnke/horizon-db-driver.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/fabianpnke/horizon-db-driver"><img src="https://img.shields.io/packagist/php-v/fabianpnke/horizon-db-driver.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/fabianpnke/horizon-db-driver"><img src="https://badge.laravel.cloud/badge/fabianpnke/horizon-db-driver?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/fabianpnke/horizon-db-driver/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/fabianpnke/horizon-db-driver/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/fabianpnke/horizon-db-driver"><img src="https://img.shields.io/packagist/dt/fabianpnke/horizon-db-driver.svg?style=flat-square" alt="Total Downloads"></a>
</p>

A database storage driver for [Laravel Horizon](https://github.com/laravel/horizon), for teams that want Horizon's dashboard and job-monitoring workflow without running Redis. It swaps Horizon's Redis-backed lock, repositories, and command queue for SQL-table-backed equivalents, and registers a `database` queue connector that emits the same Horizon events the Redis connector does.

This package does not fork or modify Horizon — it requires the official `laravel/horizon` package and layers a second service provider on top that rebinds the relevant container singletons after Horizon's own provider has registered them.

## Origin

All of the database driver logic in this package — the repositories, the lock, the command queue, the queue connector, the migration — is [Steve Bauman](https://github.com/stevebauman)'s work from [laravel/horizon#1762](https://github.com/laravel/horizon/pull/1762), a pull request against Horizon itself. Taylor Otwell closed it with:

> While I respect the effort and the idea, I don't personally want to be responsible for maintaining a DB version of Horizon right now. I think it could be a cool community package / fork though!

This package is that community package: Steve's implementation adapted to run alongside the official `laravel/horizon` package via container bindings, instead of as a fork of it.

## Installation

You can install the package via Composer:

```bash
composer require fabianpnke/horizon-db-driver
```

This package requires `laravel/horizon` to already be installed and configured (see [Horizon's own installation docs](https://laravel.com/docs/horizon#installation)).

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="horizon-db-driver-migrations"
php artisan migrate
```

The migration creates the `horizon_jobs`, `horizon_tags`, `horizon_monitored_tags`, `horizon_supervisors`, `horizon_master_supervisors`, `horizon_processes`, `horizon_metrics`, `horizon_metric_snapshots`, `horizon_metric_meta`, `horizon_command_queue`, and `horizon_locks` tables.

You may also publish the config file:

```bash
php artisan vendor:publish --tag="horizon-db-driver-config"
```

## Usage

Once installed, the package is active by default — Horizon's Redis-backed lock, repositories, and command queue are replaced with database-backed implementations, and a `database` queue connector is registered.

Point at least one of your `config/queue.php` connections at that connector so Horizon has jobs to work:

```php
'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
    ],
],
```

> **Note:** enabling this package makes *every* queue connection configured with driver `database` — not just the one(s) Horizon supervises — emit Horizon's job lifecycle events. This matches the upstream `database` connector name so behavior is a drop-in replacement, but it does mean a plain, non-Horizon `database` queue connection in the same app will also start showing up in Horizon's dashboard.

No Horizon config changes are required — this package doesn't read Horizon's own `config/horizon.php`, it activates based on its own `enabled` flag (default `true`). To turn it off per-environment without removing the package:

```env
HORIZON_DB_DRIVER_ENABLED=false
```

By default the package uses your application's default database connection. To point it at a different one:

```env
HORIZON_DB_DRIVER_CONNECTION=horizon
```

### Notes when switching from Redis

Horizon itself still assumes a few Redis-specific defaults, independent of which storage driver is active:

- `config('horizon.waits')` keys are formatted `"{connection}:{queue}"` and default to `redis:default`. Add an entry keyed with your database queue connection's name (e.g. `database:default`) if you want accurate "long wait detected" notifications.
- The published `config/horizon.php` stub hardcodes `'connection' => 'redis'` under `defaults.supervisor-1`. Change it to your database queue connection's name so your supervisors actually work the right queue.
- Horizon's own service provider unconditionally calls `Horizon::use()` on boot, which expects `config('database.redis.{connection}')` to exist and throws if it's missing — even though this package never touches Redis. Most Laravel apps ship with a default Redis connection block already, so this is rarely an issue in practice, but if your app has removed it entirely, Horizon won't boot regardless of which driver you're using.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Horizon Db Driver! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Steve Bauman](https://github.com/stevebauman) — original author of the database driver implementation this package is built on, from [laravel/horizon#1762](https://github.com/laravel/horizon/pull/1762)
- [fabianpnke](https://github.com/fabianpnke)
- [All Contributors](../../contributors)

## License

Horizon Db Driver is open-sourced software licensed under the [MIT license](LICENSE.md).
