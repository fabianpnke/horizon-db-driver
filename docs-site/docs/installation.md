---
id: installation
sidebar_position: 3
title: Installation
---

# Installation

You can install the package via Composer:

```bash
composer require fabianpnke/horizon-db-driver
```

This package requires `laravel/horizon` to already be installed and configured (see [Horizon's own installation docs](https://laravel.com/docs/horizon#installation)).

The package's service provider is discovered automatically via [Laravel's package auto-discovery](https://laravel.com/docs/packages#package-discovery) — no manual registration needed.

## Publish and run the migrations

```bash
php artisan vendor:publish --tag="horizon-db-driver-migrations"
php artisan migrate
```

The migration creates the tables the database driver stores Horizon's state in:

- `horizon_jobs`
- `horizon_tags`
- `horizon_monitored_tags`
- `horizon_supervisors`
- `horizon_master_supervisors`
- `horizon_processes`
- `horizon_metrics`
- `horizon_metric_snapshots`
- `horizon_metric_meta`
- `horizon_command_queue`
- `horizon_locks`

## Publish the config file (optional)

```bash
php artisan vendor:publish --tag="horizon-db-driver-config"
```

This publishes `config/horizon-db-driver.php`, covered in [Configuration](./configuration.md).

## Next step

Continue to [Usage](./usage.md) to point a queue connection at the `database` driver so Horizon has jobs to work.
