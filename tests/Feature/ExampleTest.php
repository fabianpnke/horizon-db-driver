<?php

declare(strict_types=1);

use HorizonDbDriver\HorizonDbDriver\HorizonDbDriver;

it('resolves the singleton', function () {
    expect(app(HorizonDbDriver::class))->toBeInstanceOf(HorizonDbDriver::class);
});

it('returns the same instance from the container', function () {
    expect(app(HorizonDbDriver::class))->toBe(app(HorizonDbDriver::class));
});

it('merges the package config', function () {
    expect(config('horizon-db-driver.placeholder'))->toBe('default');
});

it('loads the package translations', function () {
    expect(trans('horizon-db-driver::messages.placeholder'))->toBe('HorizonDbDriver placeholder translation.');
});

it('loads the package views', function () {
    expect(view()->exists('horizon-db-driver::placeholder'))->toBeTrue();
});

it('registers the artisan command', function () {
    $this->artisan('horizon-db-driver:placeholder')
        ->expectsOutputToContain('HorizonDbDriver placeholder command executed.')
        ->assertSuccessful();
});
