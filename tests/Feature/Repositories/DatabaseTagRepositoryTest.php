<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Tests\Feature\Repositories;

use HorizonDbDriver\HorizonDbDriver\Repositories\DatabaseTagRepository;
use HorizonDbDriver\HorizonDbDriver\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class DatabaseTagRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stores_and_retrieves_job_ids_by_tag(): void
    {
        $tags = $this->app->make(DatabaseTagRepository::class);

        $tags->add('job-1', ['emails', 'reports']);
        $tags->add('job-2', ['emails']);

        $this->assertEqualsCanonicalizing(['job-1', 'job-2'], $tags->jobs('emails'));
        $this->assertSame(['job-1'], $tags->jobs('reports'));
        $this->assertSame(2, $tags->count('emails'));
    }

    #[Test]
    public function it_monitors_and_stops_monitoring_tags(): void
    {
        $tags = $this->app->make(DatabaseTagRepository::class);

        $tags->monitor('emails');

        $this->assertSame(['emails'], $tags->monitoring());
        $this->assertSame(['emails'], $tags->monitored(['emails', 'reports']));

        $tags->stopMonitoring('emails');

        $this->assertSame([], $tags->monitoring());
    }

    #[Test]
    public function it_forgets_a_tag_entirely(): void
    {
        $tags = $this->app->make(DatabaseTagRepository::class);

        $tags->add('job-1', ['emails']);
        $tags->forget('emails');

        $this->assertSame([], $tags->jobs('emails'));
    }

    #[Test]
    public function it_removes_expired_temporary_tags_from_storage_but_keeps_permanent_ones(): void
    {
        $tags = $this->app->make(DatabaseTagRepository::class);

        $tags->add('job-1', ['permanent']);
        $tags->addTemporary(-1, 'job-2', ['expired']);

        $tags->trimExpired();

        $this->assertTrue(DB::table('horizon_tags')->where('tag', 'permanent')->exists());
        $this->assertFalse(DB::table('horizon_tags')->where('tag', 'expired')->exists());
    }
}
