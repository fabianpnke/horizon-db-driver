---
id: introduction
slug: /
sidebar_position: 1
title: Introduction
---

# Horizon Db Driver

A database storage driver for [Laravel Horizon](https://github.com/laravel/horizon), for teams that want Horizon's dashboard and job-monitoring workflow without running Redis. It swaps Horizon's Redis-backed lock, repositories, and command queue for SQL-table-backed equivalents, and registers a `database` queue connector that emits the same Horizon events the Redis connector does.

This package does **not** fork or modify Horizon. It requires the official `laravel/horizon` package and layers a second service provider on top that rebinds the relevant container singletons after Horizon's own provider has registered them.

## Requirements

- PHP `^8.0`
- Laravel (`illuminate/contracts`, `illuminate/database`, `illuminate/support`) `^9.21`, `^10.0`, `^11.0`, `^12.0`, or `^13.0`
- `laravel/horizon` `^5.0`, already installed and configured — see [Horizon's own installation docs](https://laravel.com/docs/horizon#installation)

## Origin

All of the database driver logic in this package — the repositories, the lock, the command queue, the queue connector, the migration — is [Steve Bauman](https://github.com/stevebauman)'s work from [laravel/horizon#1762](https://github.com/laravel/horizon/pull/1762), a pull request against Horizon itself. Taylor Otwell closed it with:

> While I respect the effort and the idea, I don't personally want to be responsible for maintaining a DB version of Horizon right now. I think it could be a cool community package / fork though!

This package is that community package: Steve's implementation adapted to run alongside the official `laravel/horizon` package via container bindings, instead of as a fork of it.

## Next steps

- [Quick Start](./quickstart.md) — the fastest path to a working dashboard.
- [Installation](./installation.md) — install the package and publish its migrations.
- [Configuration](./configuration.md) — the `enabled` flag and connection option.
- [Usage](./usage.md) — point a queue connection at the `database` driver.
- [Switching from Redis](./switching-from-redis.md) — Horizon defaults that still assume Redis.
