<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use HorizonDbDriver\HorizonDbDriver\Listeners\TrimTags;
use HorizonDbDriver\HorizonDbDriver\Repositories\DatabaseTagRepository;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Events\MasterSupervisorLooped;
use Laravel\Horizon\MasterSupervisor;

it('trims expired tags once the configured frequency has elapsed', function () {
    $tags = app(DatabaseTagRepository::class);
    $tags->add('job-1', ['expired-tag']);

    DB::table('horizon_tags')->where('tag', 'expired-tag')->update(['expires_at' => now()->subMinute()->getTimestamp()]);

    $listener = app(TrimTags::class);
    $listener->lastTrimmed = CarbonImmutable::now()->subMinutes(10);

    $listener->handle(new MasterSupervisorLooped(new MasterSupervisor));

    expect(DB::table('horizon_tags')->where('tag', 'expired-tag')->exists())->toBeFalse();
});

it('does not trim again before the configured frequency has elapsed', function () {
    $tags = app(DatabaseTagRepository::class);
    $tags->add('job-1', ['expired-tag']);

    DB::table('horizon_tags')->where('tag', 'expired-tag')->update(['expires_at' => now()->subMinute()->getTimestamp()]);

    $listener = app(TrimTags::class);
    $listener->lastTrimmed = CarbonImmutable::now();

    $listener->handle(new MasterSupervisorLooped(new MasterSupervisor));

    expect(DB::table('horizon_tags')->where('tag', 'expired-tag')->exists())->toBeTrue();
});
