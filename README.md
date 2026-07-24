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

Horizon Db Driver

## Installation

You can install the package via Composer:

```bash
composer require fabianpnke/horizon-db-driver
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="horizon-db-driver"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="horizon-db-driver-config"
```

### Publishing and Running the Migrations

```bash
php artisan vendor:publish --tag="horizon-db-driver-migrations"
php artisan migrate
```

### Publishing the Views

```bash
php artisan vendor:publish --tag="horizon-db-driver-views"
```

### Publishing the Translations

```bash
php artisan vendor:publish --tag="horizon-db-driver-lang"
```

### Publishing the Public Assets

```bash
php artisan vendor:publish --tag="horizon-db-driver-assets"
```

## Usage

<!-- Add a basic usage example here. -->

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Horizon Db Driver! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [fabianpnke](https://github.com/fabianpnke)
- [All Contributors](../../contributors)

## License

Horizon Db Driver is open-sourced software licensed under the [MIT license](LICENSE.md).
