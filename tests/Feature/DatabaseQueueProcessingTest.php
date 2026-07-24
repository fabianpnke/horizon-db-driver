<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Tests\Feature;

use HorizonDbDriver\HorizonDbDriver\Jobs\DatabaseJob;
use HorizonDbDriver\HorizonDbDriver\Tests\TestCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Contracts\JobRepository;
use PHPUnit\Framework\Attributes\Test;

class HorizonDbDriverTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(): void
    {
        //
    }
}

class DatabaseQueueProcessingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_pushed_job_in_the_horizon_jobs_table(): void
    {
        HorizonDbDriverTestJob::dispatch()->onConnection('database');

        $row = DB::table('horizon_jobs')->first();

        $this->assertNotNull($row);
        $this->assertSame('pending', $row->status);
        $this->assertSame('database', $row->connection);

        $this->assertSame(1, $this->app->make(JobRepository::class)->countPending());
    }

    #[Test]
    public function it_marks_the_job_as_reserved_when_popped_off_the_queue(): void
    {
        HorizonDbDriverTestJob::dispatch()->onConnection('database');

        $job = Queue::connection('database')->pop();

        $this->assertInstanceOf(DatabaseJob::class, $job);

        $row = DB::table('horizon_jobs')->first();

        $this->assertSame('reserved', $row->status);
    }

    #[Test]
    public function it_marks_the_job_as_completed_once_it_is_deleted_from_the_queue(): void
    {
        HorizonDbDriverTestJob::dispatch()->onConnection('database');

        $job = Queue::connection('database')->pop();
        $job->fire();

        $row = DB::table('horizon_jobs')->first();

        $this->assertSame('completed', $row->status);
    }
}
