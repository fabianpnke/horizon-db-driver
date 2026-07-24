<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Tests\Feature\Listeners;

use HorizonDbDriver\HorizonDbDriver\Tests\TestCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Events\JobFailed as HorizonJobFailed;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Throwable;

class MarshalDatabaseFailedEventTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(): void
    {
        throw new RuntimeException('failed on purpose');
    }
}

class MarshalDatabaseFailedEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marshals_a_failed_database_job_into_a_horizon_job_failed_event(): void
    {
        Event::fake([HorizonJobFailed::class]);

        MarshalDatabaseFailedEventTestJob::dispatch()->onConnection('database');

        $job = Queue::connection('database')->pop();

        try {
            $job->fire();
        } catch (Throwable $exception) {
            // Mirrors what Illuminate\Queue\Worker::process() does on failure.
            $job->fail($exception);
        }

        Event::assertDispatched(HorizonJobFailed::class);
    }
}
