---
id: configuration
sidebar_position: 4
title: Configuration
---

# Configuration

Once installed, the package is active by default — no configuration is required to get going. Publish the config file if you want to change either of the two options it exposes:

```bash
php artisan vendor:publish --tag="horizon-db-driver-config"
```

This publishes `config/horizon-db-driver.php`:

```php
return [
    'enabled' => env('HORIZON_DB_DRIVER_ENABLED', true),

    'connection' => env('HORIZON_DB_DRIVER_CONNECTION'),
];
```

## Enabling and disabling the driver

The package doesn't read Horizon's own `config/horizon.php` — it activates based on its own `enabled` flag (default `true`). To turn it off per-environment without removing the package, e.g. to fall back to Horizon's default Redis driver:

```env
HORIZON_DB_DRIVER_ENABLED=false
```

## Choosing a database connection

By default the package uses your application's default database connection (`config('database.default')`). To point it at a different one instead:

```env
HORIZON_DB_DRIVER_CONNECTION=horizon
```

The value must match a connection name defined in `config/database.php`.
