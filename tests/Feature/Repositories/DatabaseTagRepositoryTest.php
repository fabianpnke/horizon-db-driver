<?php

declare(strict_types=1);

use HorizonDbDriver\HorizonDbDriver\Repositories\DatabaseTagRepository;
use Illuminate\Support\Facades\DB;

it('stores and retrieves job ids by tag', function () {
    $tags = app(DatabaseTagRepository::class);

    $tags->add('job-1', ['emails', 'reports']);
    $tags->add('job-2', ['emails']);

    expect($tags->jobs('emails'))->toEqualCanonicalizing(['job-1', 'job-2']);
    expect($tags->jobs('reports'))->toBe(['job-1']);
    expect($tags->count('emails'))->toBe(2);
});

it('monitors and stops monitoring tags', function () {
    $tags = app(DatabaseTagRepository::class);

    $tags->monitor('emails');

    expect($tags->monitoring())->toBe(['emails']);
    expect($tags->monitored(['emails', 'reports']))->toBe(['emails']);

    $tags->stopMonitoring('emails');

    expect($tags->monitoring())->toBe([]);
});

it('forgets a tag entirely', function () {
    $tags = app(DatabaseTagRepository::class);

    $tags->add('job-1', ['emails']);
    $tags->forget('emails');

    expect($tags->jobs('emails'))->toBe([]);
});

it('removes expired temporary tags from storage but keeps permanent ones', function () {
    $tags = app(DatabaseTagRepository::class);

    $tags->add('job-1', ['permanent']);
    $tags->addTemporary(-1, 'job-2', ['expired']);

    $tags->trimExpired();

    expect(DB::table('horizon_tags')->where('tag', 'permanent')->exists())->toBeTrue();
    expect(DB::table('horizon_tags')->where('tag', 'expired')->exists())->toBeFalse();
});
