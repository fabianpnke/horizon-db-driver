---
id: usage
sidebar_position: 5
title: Usage
---

# Usage

Once installed, Horizon's Redis-backed lock, repositories, and command queue are replaced with database-backed implementations, and a `database` queue connector is registered.

## Point a queue connection at the driver

Point at least one of your `config/queue.php` connections at the `database` driver so Horizon has jobs to work:

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

This is the same `database` connector name Laravel's own non-Horizon queue driver uses, so it's a drop-in replacement for supervisors that were previously pointed at `redis`.

:::caution
Enabling this package makes **every** queue connection configured with driver `database` — not just the one(s) Horizon supervises — emit Horizon's job lifecycle events. This does mean a plain, non-Horizon `database` queue connection in the same app will also start showing up in Horizon's dashboard.
:::

## No Horizon config changes required

This package doesn't read Horizon's own `config/horizon.php` — see [Configuration](./configuration.md) for the `enabled` flag and `connection` option that control it instead.

## Next step

A few of Horizon's own defaults still assume Redis regardless of which storage driver is active — see [Switching from Redis](./switching-from-redis.md) for what to change.
