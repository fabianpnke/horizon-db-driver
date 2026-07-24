<?php

declare(strict_types=1);

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Events\JobFailed as HorizonJobFailed;

class HorizonDbDriverFailingTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(): void
    {
        throw new RuntimeException('failed on purpose');
    }
}

it('marshals a failed database job into a Horizon JobFailed event', function () {
    Event::fake([HorizonJobFailed::class]);

    HorizonDbDriverFailingTestJob::dispatch()->onConnection('database');

    $job = Queue::connection('database')->pop();

    try {
        $job->fire();
    } catch (Throwable $exception) {
        // Mirrors what Illuminate\Queue\Worker::process() does on failure.
        $job->fail($exception);
    }

    Event::assertDispatched(HorizonJobFailed::class);
});
