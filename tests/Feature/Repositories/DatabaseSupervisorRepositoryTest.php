<?php

declare(strict_types=1);

use HorizonDbDriver\HorizonDbDriver\Repositories\DatabaseSupervisorRepository;
use Illuminate\Support\Facades\DB;

it('stores and retrieves supervisor information', function () {
    DB::table('horizon_supervisors')->insert([
        'name' => 'horizon-1:supervisor-1',
        'master' => 'horizon-1',
        'pid' => 123,
        'status' => 'running',
        'processes' => json_encode(['redis:default' => 1]),
        'options' => json_encode(['timeout' => 60]),
        'expires_at' => now()->addSeconds(30)->getTimestamp(),
        'updated_at' => now()->getTimestamp(),
    ]);

    $supervisors = app(DatabaseSupervisorRepository::class);

    expect($supervisors->names())->toBe(['horizon-1:supervisor-1']);

    $found = $supervisors->find('horizon-1:supervisor-1');

    expect($found->status)->toBe('running');
    expect($found->processes)->toBe(['redis:default' => 1]);
});

it('removes expired supervisors from storage', function () {
    DB::table('horizon_supervisors')->insert([
        'name' => 'horizon-1:supervisor-1',
        'master' => 'horizon-1',
        'pid' => 123,
        'status' => 'running',
        'processes' => json_encode([]),
        'options' => json_encode([]),
        'expires_at' => now()->subSecond()->getTimestamp(),
        'updated_at' => now()->getTimestamp(),
    ]);

    $supervisors = app(DatabaseSupervisorRepository::class);
    $supervisors->flushExpired();

    expect(DB::table('horizon_supervisors')->exists())->toBeFalse();
});

it('forgets a supervisor by name', function () {
    DB::table('horizon_supervisors')->insert([
        'name' => 'horizon-1:supervisor-1',
        'master' => 'horizon-1',
        'pid' => 123,
        'status' => 'running',
        'processes' => json_encode([]),
        'options' => json_encode([]),
        'expires_at' => now()->addSeconds(30)->getTimestamp(),
        'updated_at' => now()->getTimestamp(),
    ]);

    $supervisors = app(DatabaseSupervisorRepository::class);
    $supervisors->forget('horizon-1:supervisor-1');

    expect(DB::table('horizon_supervisors')->exists())->toBeFalse();
});
