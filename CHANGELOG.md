# Release Notes

## [Unreleased](https://github.com/fabianpnke/horizon-db-driver/compare/v0.1.0...HEAD)

## [v0.1.0](https://github.com/fabianpnke/horizon-db-driver/compare/v0.1.0...v0.1.0) - 2026-07-24

Initial release.

A database storage driver for [Laravel Horizon](https://github.com/laravel/horizon), for teams that want Horizon's dashboard and job-monitoring workflow without running Redis. Swaps Horizon's Redis-backed lock, repositories, and command queue for SQL-table-backed equivalents, and registers a `database` queue connector that emits the same Horizon events the Redis connector does.

Built on [Steve Bauman](https://github.com/stevebauman)'s implementation from [laravel/horizon#1762](https://github.com/laravel/horizon/pull/1762).

**Requirements:** PHP ^8.0 · Laravel 9–13 · `laravel/horizon` ^5.0

**Docs:** https://fabianpnke.github.io/horizon-db-driver/
