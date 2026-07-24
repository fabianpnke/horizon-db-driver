<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Tests\Feature\Listeners;

use Carbon\CarbonImmutable;
use HorizonDbDriver\HorizonDbDriver\Listeners\TrimTags;
use HorizonDbDriver\HorizonDbDriver\Repositories\DatabaseTagRepository;
use HorizonDbDriver\HorizonDbDriver\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Events\MasterSupervisorLooped;
use Laravel\Horizon\MasterSupervisor;

class TrimTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_trims_expired_tags_once_the_configured_frequency_has_elapsed(): void
    {
        $tags = $this->app->make(DatabaseTagRepository::class);
        $tags->add('job-1', ['expired-tag']);

        DB::table('horizon_tags')->where('tag', 'expired-tag')->update(['expires_at' => now()->subMinute()->getTimestamp()]);

        $listener = $this->app->make(TrimTags::class);
        $listener->lastTrimmed = CarbonImmutable::now()->subMinutes(10);

        $listener->handle(new MasterSupervisorLooped(new MasterSupervisor));

        $this->assertFalse(DB::table('horizon_tags')->where('tag', 'expired-tag')->exists());
    }

    public function test_it_does_not_trim_again_before_the_configured_frequency_has_elapsed(): void
    {
        $tags = $this->app->make(DatabaseTagRepository::class);
        $tags->add('job-1', ['expired-tag']);

        DB::table('horizon_tags')->where('tag', 'expired-tag')->update(['expires_at' => now()->subMinute()->getTimestamp()]);

        $listener = $this->app->make(TrimTags::class);
        $listener->lastTrimmed = CarbonImmutable::now();

        $listener->handle(new MasterSupervisorLooped(new MasterSupervisor));

        $this->assertTrue(DB::table('horizon_tags')->where('tag', 'expired-tag')->exists());
    }
}
