---
name: horizon-db-driver-development
description: >
  Configure and apply the Horizon Db Driver package in Laravel applications that
  want Laravel Horizon without a Redis dependency.
license: MIT
metadata:
  author: fabianpnke
---

# Horizon Db Driver

Use this skill when a Laravel application needs Horizon's dashboard and job monitoring
but wants to store Horizon's meta information (jobs, tags, supervisors, metrics, locks)
in SQL tables instead of Redis.

## Primary Goal

- get a Laravel app running Horizon against a database-backed storage/queue driver, using
  `fabianpnke/horizon-db-driver`'s public config keys and publish tags only

## How it works

The package requires `laravel/horizon` (it does not fork or replace it) and layers a
second service provider on top that, when enabled, rebinds Horizon's Redis-backed `Lock`,
repositories, and command queue to database-backed equivalents, and registers a
`database` queue connector that emits the same Horizon lifecycle events the Redis
connector does. No changes to `config/horizon.php` are needed or read by this package.

## Workflow

### 1. Confirm prerequisites

- `laravel/horizon` must already be installed and configured in the app
- `fabianpnke/horizon-db-driver` must be required via Composer

### 2. Publish and run the migrations

```bash
php artisan vendor:publish --tag="horizon-db-driver-migrations"
php artisan migrate
```

This creates `horizon_jobs`, `horizon_tags`, `horizon_monitored_tags`,
`horizon_supervisors`, `horizon_master_supervisors`, `horizon_processes`,
`horizon_metrics`, `horizon_metric_snapshots`, `horizon_metric_meta`,
`horizon_command_queue`, and `horizon_locks`.

### 3. Point a queue connection at the `database` driver

Horizon needs at least one `config/queue.php` connection using driver `database` to
supervise:

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

Note: enabling this package makes **every** queue connection configured with driver
`database` in the app emit Horizon events, not only the one(s) Horizon supervises.

### 4. (Optional) publish and adjust config

```bash
php artisan vendor:publish --tag="horizon-db-driver-config"
```

- `horizon-db-driver.enabled` (env `HORIZON_DB_DRIVER_ENABLED`, default `true`) — toggle
  the database driver off per environment without removing the package; when disabled,
  Horizon falls back to its normal Redis bindings.
- `horizon-db-driver.connection` (env `HORIZON_DB_DRIVER_CONNECTION`, default `null`) —
  which database connection Horizon's tables live on; `null` uses the app's default.

## Rules, References, and Templates

Read before executing:

- `README.md` — installation and usage
- `config/horizon-db-driver.php` — the two config keys this package defines
- `database/migrations/2026_01_01_000000_create_horizon_db_driver_tables.php` — table
  names and columns
- `src/HorizonDbDriverServiceProvider.php` — exact publish tags and rebound bindings

## Examples

- A team running Horizon on a host without Redis installs this package, publishes and
  runs the migration, adds a `database` queue connection, and dispatches jobs onto it —
  Horizon's dashboard shows pending/failed/completed jobs backed by the new tables.
- A team wants to keep the package installed but temporarily revert to Redis in staging:
  set `HORIZON_DB_DRIVER_ENABLED=false` in that environment's `.env`.

## Anti-Patterns

- Do not edit `config/horizon.php` or expect this package to read a `driver`/`database`
  key there — it only reads its own `config/horizon-db-driver.php`.
- Do not assume a facade, Artisan command, route, or view ships with this package — it
  is a backend-only service provider with no HTTP or console surface of its own.
- Do not point every `database` queue connection at Horizon unless that's intended — the
  connector name match means all of them will start emitting Horizon events.
