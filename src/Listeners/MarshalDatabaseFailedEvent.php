<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Listeners;

use Exception;
use HorizonDbDriver\HorizonDbDriver\Jobs\DatabaseJob;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobFailed as LaravelJobFailed;
use Laravel\Horizon\Events\JobFailed;

class MarshalDatabaseFailedEvent
{
    /**
     * The event dispatcher implementation.
     */
    public Dispatcher $events;

    /**
     * Create a new listener instance.
     */
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Handle the event.
     */
    public function handle(LaravelJobFailed $event): void
    {
        if (! $event->job instanceof DatabaseJob) {
            return;
        }

        if (! $event->exception instanceof Exception) {
            return;
        }

        $this->events->dispatch((new JobFailed(
            $event->exception, $event->job, $event->job->getRawBody(),
        ))->connection($event->connectionName)->queue($event->job->getQueue()));
    }
}
