<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver;

use HorizonDbDriver\HorizonDbDriver\Jobs\DatabaseJob;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Database\Query\Builder;
use Illuminate\Queue\DatabaseQueue as BaseQueue;
use Illuminate\Queue\Jobs\DatabaseJobRecord;
use Laravel\Horizon\Events\JobPending;
use Laravel\Horizon\Events\JobPushed;
use Laravel\Horizon\Events\JobReserved;
use Laravel\Horizon\JobPayload;

class DatabaseQueue extends BaseQueue
{
    /**
     * The job that last pushed to queue via the "push" method.
     */
    protected mixed $lastPushed = null;

    /**
     * Get the number of queue jobs that are ready to process.
     */
    public function readyNow(?string $queue = null): int
    {
        $expiration = $this->currentTime() - $this->retryAfter;

        return $this->database->table($this->table)
            ->where('queue', $this->getQueue($queue))
            ->where(function (Builder $query) use ($expiration) {
                $query->where(function (Builder $query) {
                    $query->whereNull('reserved_at')
                        ->where('available_at', '<=', $this->currentTime());
                })->orWhere('reserved_at', '<=', $expiration);
            })
            ->count();
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param  mixed  $jobs
     * @param  mixed  $data
     * @param  string|null  $queue
     */
    public function bulk($jobs, $data = '', $queue = null): void
    {
        foreach ((array) $jobs as $job) {
            $this->push($job, $data, $queue);
        }
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  mixed  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     */
    public function push($job, $data = '', $queue = null): mixed
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $this->getQueue($queue), $data),
            $queue,
            null,
            function (mixed $payload, mixed $queue) use ($job) {
                $this->lastPushed = $job;

                return $this->pushRaw($payload, $queue);
            },
        );
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string|null  $queue
     * @param  array<string, mixed>  $options
     */
    public function pushRaw($payload, $queue = null, array $options = []): mixed
    {
        $payload = (new JobPayload($payload))->prepare($this->lastPushed);

        $this->event($this->getQueue($queue), new JobPending($payload->value));

        return tap(parent::pushRaw($payload->value, $queue, $options), function () use ($payload, $queue) {
            $this->event($this->getQueue($queue), new JobPushed($payload->value));
        });
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  mixed  $delay
     * @param  mixed  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     */
    public function later($delay, $job, $data = '', $queue = null): mixed
    {
        $payload = (new JobPayload($this->createPayload($job, $queue, $data)))->prepare($job)->value;

        return $this->enqueueUsing(
            $job,
            $payload,
            $queue,
            $delay,
            function (mixed $payload, mixed $queue, mixed $delay) {
                $this->event($this->getQueue($queue), new JobPending($payload));

                return tap($this->pushToDatabase($queue, $payload, $delay), function () use ($payload, $queue) {
                    $this->event($this->getQueue($queue), new JobPushed($payload));
                });
            },
        );
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  mixed  $job
     * @param  mixed  $queue
     * @param  mixed  $data
     * @return array<string, mixed>
     */
    protected function createPayloadArray($job, $queue, $data = ''): array
    {
        $payload = parent::createPayloadArray($job, $queue, $data);

        $payload['id'] = $payload['uuid'];

        return $payload;
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string|null  $queue
     */
    public function pop($queue = null): ?JobContract
    {
        return tap(parent::pop($queue), function (?JobContract $result) use ($queue) {
            if ($result) {
                $this->event($this->getQueue($queue), new JobReserved($result->getRawBody()));
            }
        });
    }

    /**
     * Marshal the reserved job into a DatabaseJob instance.
     *
     * @param  mixed  $queue
     * @param  DatabaseJobRecord  $job
     */
    protected function marshalJob($queue, $job): DatabaseJob
    {
        $job = $this->markJobAsReserved($job);

        return new DatabaseJob(
            $this->container, $this, $job, $this->connectionName, $queue,
        );
    }

    /**
     * Fire the given event if a dispatcher is bound.
     */
    public function event(mixed $queue, mixed $event): void
    {
        if ($this->container->bound(Dispatcher::class)) {
            $this->container->make(Dispatcher::class)->dispatch(
                $event->connection($this->getConnectionName())->queue($queue),
            );
        }
    }
}
