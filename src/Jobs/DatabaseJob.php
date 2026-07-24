<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Jobs;

use HorizonDbDriver\HorizonDbDriver\DatabaseQueue;
use Illuminate\Queue\Jobs\DatabaseJob as BaseDatabaseJob;
use Laravel\Horizon\Events\JobDeleted;
use Laravel\Horizon\Events\JobReleased;

class DatabaseJob extends BaseDatabaseJob
{
    /**
     * The database queue instance.
     *
     * @var DatabaseQueue
     */
    protected $database;

    /**
     * Release the job back into the queue after (n) seconds.
     */
    public function release($delay = 0): void
    {
        parent::release($delay);

        $this->database->event($this->queue, new JobReleased($this->getRawBody()));
    }

    /**
     * Delete the job from the queue.
     */
    public function delete(): void
    {
        parent::delete();

        $this->database->event($this->queue, new JobDeleted($this, $this->getRawBody()));
    }
}
