# Release Notes

## [Unreleased](https://github.com/fabianpnke/horizon-db-driver/compare/v1.0.2...HEAD)

## [v1.0.2](https://github.com/fabianpnke/horizon-db-driver/compare/v1.0.1...v1.0.2) - 2026-08-07

<!-- Release notes generated using configuration in .github/release.yml at main -->
### What's Changed

#### Bug fixes

* fix: recover from transactions left open on the connection by @fabianpnke in https://github.com/fabianpnke/horizon-db-driver/pull/5

**Full Changelog**: https://github.com/fabianpnke/horizon-db-driver/compare/v1.0.1...v1.0.2

## [v1.0.1](https://github.com/fabianpnke/horizon-db-driver/compare/v1.0.0...v1.0.1) - 2026-07-24

### Bug Fixes

- Fixed a `TypeError` thrown from `DatabaseSupervisorRepository::longestActiveTimeout()`. Supervisor options are round-tripped through JSON storage, and `json_decode()` could hand back the `timeout` value as a numeric string instead of an int, which violated the method's declared `int` return type. The result is now explicitly cast to `int`.

**Requirements:** PHP ^8.0 · Laravel 9–13 · `laravel/horizon` ^5.0

**Docs:** https://fabianpnke.github.io/horizon-db-driver/

## [v1.0.0](https://github.com/fabianpnke/horizon-db-driver/compare/v0.1.0...v1.0.0) - 2026-07-24

No breaking changes, no code changes — the package has been running unmodified since v0.1.0. This release marks it stable.

Since v0.1.0, `horizon-db-driver` has been validated in a real production Laravel 12 application: multi-queue Horizon setup with priority-ordered queue lists, database-backed lock/repositories/command queue, master failover, and job processing all confirmed working end-to-end — with zero issues found in the package's own code.

Docs also gained a section on a related but separate Horizon gotcha found during that rollout: `php artisan horizon` itself refuses to start when `database.redis.client` is `phpredis` and `ext-redis` isn't installed — independent of this package, but relevant to anyone using it specifically to avoid a Redis dependency. See [Switching from Redis](https://fabianpnke.github.io/horizon-db-driver/switching-from-redis).

**Requirements:** PHP ^8.0 · Laravel 9–13 · `laravel/horizon` ^5.0

**Docs:** https://fabianpnke.github.io/horizon-db-driver/

## [v0.1.0](https://github.com/fabianpnke/horizon-db-driver/compare/v0.1.0...v0.1.0) - 2026-07-24

Initial release.

A database storage driver for [Laravel Horizon](https://github.com/laravel/horizon), for teams that want Horizon's dashboard and job-monitoring workflow without running Redis. Swaps Horizon's Redis-backed lock, repositories, and command queue for SQL-table-backed equivalents, and registers a `database` queue connector that emits the same Horizon events the Redis connector does.

Built on [Steve Bauman](https://github.com/stevebauman)'s implementation from [laravel/horizon#1762](https://github.com/laravel/horizon/pull/1762).

**Requirements:** PHP ^8.0 · Laravel 9–13 · `laravel/horizon` ^5.0

**Docs:** https://fabianpnke.github.io/horizon-db-driver/
