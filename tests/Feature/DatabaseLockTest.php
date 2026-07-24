<?php

declare(strict_types=1);

use Laravel\Horizon\Lock;

it('acquires and releases a lock', function () {
    $lock = app(Lock::class);

    expect($lock->get('horizon:test-lock'))->toBeTrue();
    expect($lock->exists('horizon:test-lock'))->toBeTrue();

    $lock->release('horizon:test-lock');

    expect($lock->exists('horizon:test-lock'))->toBeFalse();
});

it('does not grant a lock that is already held', function () {
    $lock = app(Lock::class);

    expect($lock->get('horizon:test-lock', 60))->toBeTrue();
    expect($lock->get('horizon:test-lock', 60))->toBeFalse();
});

it('grants a lock again once it has expired', function () {
    $lock = app(Lock::class);

    expect($lock->get('horizon:test-lock', -1))->toBeTrue();
    expect($lock->get('horizon:test-lock', 60))->toBeTrue();
});

it('runs the callback with when the lock is available', function () {
    $lock = app(Lock::class);

    $ran = false;

    $lock->with('horizon:test-lock', function () use (&$ran) {
        $ran = true;
    });

    expect($ran)->toBeTrue();
    expect($lock->exists('horizon:test-lock'))->toBeFalse();
});
