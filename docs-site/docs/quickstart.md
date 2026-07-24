---
id: quickstart
sidebar_position: 2
title: Quick Start
---

# Quick Start

The fastest path from an app with `laravel/horizon` already installed to a working Horizon dashboard backed by your database instead of Redis.

## 1. Install the package

```bash
composer require fabianpnke/horizon-db-driver
```

## 2. Publish and run the migrations

```bash
php artisan vendor:publish --tag="horizon-db-driver-migrations"
php artisan migrate
```

This creates the tables Horizon needs to store jobs, tags, supervisors, and metrics in your database — see [Installation](./installation.md) for the full table list.

## 3. Point a queue connection at the `database` driver

In `config/queue.php`:

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

## 4. Point Horizon's supervisor at that connection

In `config/horizon.php`, change the connection Horizon's supervisors use from `redis` to `database`:

```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'database',
            // ...
        ],
    ],
],
```

## 5. Run Horizon

```bash
php artisan horizon
```

Visit `/horizon` in your browser — the dashboard now runs entirely off your database, no Redis connection required for Horizon itself.

## Next steps

- [Configuration](./configuration.md) — disable the driver per-environment, or point it at a non-default database connection.
- [Usage](./usage.md) — more on the `database` queue connector and how it interacts with non-Horizon queues.
- [Switching from Redis](./switching-from-redis.md) — a couple of Horizon defaults that still assume Redis, like `config('horizon.waits')` and the `Horizon::use()` boot check.
