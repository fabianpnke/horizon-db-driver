---
id: switching-from-redis
sidebar_position: 6
title: Switching from Redis
---

# Switching from Redis

Horizon itself still assumes a few Redis-specific defaults, independent of which storage driver is active.

## Long wait notifications

`config('horizon.waits')` keys are formatted `"{connection}:{queue}"` and default to `redis:default`. Add an entry keyed with your database queue connection's name (e.g. `database:default`) if you want accurate "long wait detected" notifications:

```php
// config/horizon.php
'waits' => [
    'database:default' => 60,
],
```

## Supervisor connection

The published `config/horizon.php` stub hardcodes `'connection' => 'redis'` under `defaults.supervisor-1`. Change it to your database queue connection's name so your supervisors actually work the right queue:

```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'database',
            // ...
        ],
    ],
],
```

## A Redis connection block must still exist

Horizon's own service provider unconditionally calls `Horizon::use()` on boot, which expects `config('database.redis.{connection}')` to exist and throws if it's missing — even though this package never touches Redis. Most Laravel apps ship with a default Redis connection block already, so this is rarely an issue in practice, but if your app has removed it entirely, Horizon won't boot regardless of which driver you're using.
