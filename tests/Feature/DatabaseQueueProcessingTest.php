<?php

declare(strict_types=1);

use HorizonDbDriver\HorizonDbDriver\Jobs\DatabaseJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Events\JobPushed;

class HorizonDbDriverTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(): void
    {
        //
    }
}

it('records a pushed job in the horizon_jobs table and fires JobPushed', function () {
    Event::fake([JobPushed::class]);

    HorizonDbDriverTestJob::dispatch()->onConnection('database');

    $row = DB::table('horizon_jobs')->first();

    expect($row)->not->toBeNull();
    expect($row->status)->toBe('pending');
    expect($row->connection)->toBe('database');

    Event::assertDispatched(JobPushed::class);

    expect(app(JobRepository::class)->countPending())->toBe(1);
});

it('marks the job as reserved when popped off the queue', function () {
    HorizonDbDriverTestJob::dispatch()->onConnection('database');

    $job = Queue::connection('database')->pop();

    expect($job)->toBeInstanceOf(DatabaseJob::class);

    $row = DB::table('horizon_jobs')->first();

    expect($row->status)->toBe('reserved');
});

it('marks the job as completed once it is deleted from the queue', function () {
    HorizonDbDriverTestJob::dispatch()->onConnection('database');

    $job = Queue::connection('database')->pop();
    $job->fire();

    $row = DB::table('horizon_jobs')->first();

    expect($row->status)->toBe('completed');
});
