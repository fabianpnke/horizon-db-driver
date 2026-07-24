<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\JobPayload;
use stdClass;
use Throwable;

class DatabaseJobRepository implements JobRepository
{
    /**
     * The database connection resolver instance.
     */
    public ConnectionResolverInterface $resolver;

    /**
     * The columns selected when reading jobs.
     *
     * @var array<int, string>
     */
    public array $keys = [
        'id', 'connection', 'queue', 'name', 'status', 'payload',
        'exception', 'context', 'failed_at', 'completed_at', 'retried_by',
        'reserved_at', 'delay', 'monitored',
    ];

    /**
     * The number of minutes until recently failed jobs should be purged.
     */
    public int $recentFailedJobExpires;

    /**
     * The number of minutes until recent jobs should be purged.
     */
    public int $recentJobExpires;

    /**
     * The number of minutes until pending jobs should be purged.
     */
    public int $pendingJobExpires;

    /**
     * The number of minutes until completed and silenced jobs should be purged.
     */
    public int $completedJobExpires;

    /**
     * The number of minutes until failed jobs should be purged.
     */
    public int $failedJobExpires;

    /**
     * The number of minutes until monitored jobs should be purged.
     */
    public int $monitoredJobExpires;

    /**
     * Create a new repository instance.
     */
    public function __construct(ConnectionResolverInterface $resolver)
    {
        $this->resolver = $resolver;
        $this->recentJobExpires = config('horizon.trim.recent', 60);
        $this->pendingJobExpires = config('horizon.trim.pending', 60);
        $this->completedJobExpires = config('horizon.trim.completed', 60);
        $this->failedJobExpires = config('horizon.trim.failed', 10080);
        $this->recentFailedJobExpires = config('horizon.trim.recent_failed', $this->failedJobExpires);
        $this->monitoredJobExpires = config('horizon.trim.monitored', 10080);
    }

    /**
     * Get the next job ID that should be assigned.
     */
    public function nextJobId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Get the total count of recent jobs.
     */
    public function totalRecent(): int
    {
        return $this->countRecent();
    }

    /**
     * Get the total count of failed jobs.
     */
    public function totalFailed(): int
    {
        return $this->table()->where('status', 'failed')->count();
    }

    /**
     * Get a chunk of recent jobs.
     *
     * @return Collection<int, stdClass>
     */
    public function getRecent(mixed $afterIndex = null): Collection
    {
        return $this->getJobsByQuery(
            $this->table()->where('created_at', '>=', $this->cutoffTime($this->recentJobExpires)),
            $afterIndex,
        );
    }

    /**
     * Get a chunk of failed jobs.
     *
     * @return Collection<int, stdClass>
     */
    public function getFailed(mixed $afterIndex = null): Collection
    {
        return $this->getJobsByQuery(
            $this->table()
                ->where('status', 'failed')
                ->where('failed_at', '>=', $this->cutoffTime($this->failedJobExpires)),
            $afterIndex,
            'failed_at',
        );
    }

    /**
     * Get a chunk of pending jobs.
     *
     * @return Collection<int, stdClass>
     */
    public function getPending(mixed $afterIndex = null): Collection
    {
        return $this->getJobsByQuery(
            $this->table()
                ->whereIn('status', ['pending', 'reserved'])
                ->where('created_at', '>=', $this->cutoffTime($this->pendingJobExpires)),
            $afterIndex,
        );
    }

    /**
     * Get a chunk of completed jobs.
     *
     * @return Collection<int, stdClass>
     */
    public function getCompleted(mixed $afterIndex = null): Collection
    {
        return $this->getJobsByQuery(
            $this->table()
                ->where('status', 'completed')
                ->where('completed_at', '>=', $this->cutoffTime($this->completedJobExpires)),
            $afterIndex,
            'completed_at',
        );
    }

    /**
     * Get a chunk of silenced jobs.
     *
     * @return Collection<int, stdClass>
     */
    public function getSilenced(mixed $afterIndex = null): Collection
    {
        return $this->getJobsByQuery(
            $this->table()
                ->where('status', 'silenced')
                ->where('completed_at', '>=', $this->cutoffTime($this->completedJobExpires)),
            $afterIndex,
            'completed_at',
        );
    }

    /**
     * Get the count of recent jobs.
     */
    public function countRecent(): int
    {
        return $this->table()
            ->where('created_at', '>=', $this->cutoffTime($this->recentJobExpires))
            ->count();
    }

    /**
     * Get the count of failed jobs.
     */
    public function countFailed(): int
    {
        return $this->table()
            ->where('status', 'failed')
            ->where('failed_at', '>=', $this->cutoffTime($this->failedJobExpires))
            ->count();
    }

    /**
     * Get the count of pending jobs.
     */
    public function countPending(): int
    {
        return $this->table()
            ->whereIn('status', ['pending', 'reserved'])
            ->where('created_at', '>=', $this->cutoffTime($this->pendingJobExpires))
            ->count();
    }

    /**
     * Get the count of completed jobs.
     */
    public function countCompleted(): int
    {
        return $this->table()
            ->where('status', 'completed')
            ->where('completed_at', '>=', $this->cutoffTime($this->completedJobExpires))
            ->count();
    }

    /**
     * Get the count of silenced jobs.
     */
    public function countSilenced(): int
    {
        return $this->table()
            ->where('status', 'silenced')
            ->where('completed_at', '>=', $this->cutoffTime($this->completedJobExpires))
            ->count();
    }

    /**
     * Get the count of the recently failed jobs.
     */
    public function countRecentlyFailed(): int
    {
        return $this->table()
            ->where('status', 'failed')
            ->where('failed_at', '>=', $this->cutoffTime($this->recentFailedJobExpires))
            ->count();
    }

    /**
     * Get the cutoff timestamp for the given number of minutes.
     */
    protected function cutoffTime(int $minutes): float
    {
        return CarbonImmutable::now()->subMinutes($minutes)->getTimestamp();
    }

    /**
     * Get a chunk of jobs from the given query.
     *
     * @return Collection<int, stdClass>
     */
    protected function getJobsByQuery(Builder $query, mixed $afterIndex, string $orderBy = 'created_at'): Collection
    {
        $afterIndex = $afterIndex === null ? -1 : (int) $afterIndex;

        $records = $query->orderBy($orderBy, 'desc')
            ->orderBy('id', 'desc')
            ->offset($afterIndex + 1)
            ->limit(50)
            ->get();

        return $this->indexJobs(collect($records)->map(function (stdClass $record) {
            return $this->fromRecord($record);
        }), $afterIndex + 1);
    }

    /**
     * Retrieve the jobs with the given IDs.
     *
     * @param  array<int, mixed>  $ids
     * @return Collection<int, stdClass>
     */
    public function getJobs(array $ids, mixed $indexFrom = 0): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        $records = $this->table()
            ->whereIn('id', array_map('strval', $ids))
            ->get()
            ->keyBy('id');

        $jobs = collect($ids)->map(function (mixed $id) use ($records) {
            return isset($records[(string) $id]) ? $this->fromRecord($records[(string) $id]) : null;
        })->filter()->values();

        return $this->indexJobs($jobs, $indexFrom);
    }

    /**
     * Convert a database record into a stdClass job object.
     */
    protected function fromRecord(object $record): stdClass
    {
        $job = new stdClass;

        foreach ($this->keys as $key) {
            $job->{$key} = $record->{$key} ?? null;
        }

        return $job;
    }

    /**
     * Index the given jobs from the given index.
     *
     * @param  Collection<int, stdClass>  $jobs
     * @return Collection<int, stdClass>
     */
    protected function indexJobs(Collection $jobs, int $indexFrom): Collection
    {
        return $jobs->values()->map(function (stdClass $job) use (&$indexFrom) {
            $job->index = $indexFrom;

            $indexFrom++;

            return $job;
        });
    }

    /**
     * Insert the job into storage.
     */
    public function pushed(mixed $connection, mixed $queue, JobPayload $payload): void
    {
        $time = $this->microtime();

        $this->table()->upsert([
            'id' => $payload->id(),
            'connection' => $connection,
            'queue' => $queue,
            'name' => $payload->decoded['displayName'],
            'status' => 'pending',
            'payload' => $payload->value,
            'created_at' => $time,
            'updated_at' => $time,
            'monitored' => false,
        ], ['id'], [
            'connection', 'queue', 'name', 'status', 'payload', 'updated_at',
        ]);
    }

    /**
     * Mark the job as reserved.
     */
    public function reserved(mixed $connection, mixed $queue, JobPayload $payload): void
    {
        $time = $this->microtime();

        $this->table()->where('id', $payload->id())->update([
            'status' => 'reserved',
            'payload' => $payload->value,
            'updated_at' => $time,
            'reserved_at' => $time,
        ]);
    }

    /**
     * Mark the job as released / pending.
     */
    public function released(mixed $connection, mixed $queue, JobPayload $payload, mixed $delay = 0): void
    {
        $this->table()->where('id', $payload->id())->update([
            'status' => 'pending',
            'payload' => $payload->value,
            'updated_at' => $this->microtime(),
            'delay' => $delay,
        ]);
    }

    /**
     * Mark the job as completed and monitored.
     */
    public function remember(mixed $connection, mixed $queue, JobPayload $payload): void
    {
        $time = $this->microtime();

        $this->table()->upsert([
            'id' => $payload->id(),
            'connection' => $connection,
            'queue' => $queue,
            'name' => $payload->decoded['displayName'],
            'status' => 'completed',
            'payload' => $payload->value,
            'completed_at' => $time,
            'created_at' => $time,
            'updated_at' => $time,
            'monitored' => true,
        ], ['id'], [
            'connection', 'queue', 'name', 'status', 'payload',
            'completed_at', 'updated_at', 'monitored',
        ]);
    }

    /**
     * Mark the given jobs as released / pending.
     *
     * @param  Collection<int, JobPayload>  $payloads
     */
    public function migrated(mixed $connection, mixed $queue, Collection $payloads): void
    {
        if ($payloads->isEmpty()) {
            return;
        }

        $ids = $payloads->map(fn (JobPayload $payload) => $payload->id())->all();

        $this->table()->whereIn('id', $ids)->update([
            'status' => 'pending',
            'delay' => 0,
            'updated_at' => $this->microtime(),
        ]);
    }

    /**
     * Handle the storage of a completed job.
     */
    public function completed(JobPayload $payload, mixed $failed = false, mixed $silenced = false): void
    {
        if ($payload->isRetry()) {
            $this->updateRetryInformationOnParent($payload, $failed);
        }

        if ($failed) {
            return;
        }

        $this->table()->where('id', $payload->id())->update([
            'status' => $silenced ? 'silenced' : 'completed',
            'completed_at' => $this->microtime(),
        ]);
    }

    /**
     * Update the retry status of a job's parent.
     */
    protected function updateRetryInformationOnParent(JobPayload $payload, bool $failed): void
    {
        $this->connection()->transaction(function () use ($payload, $failed) {
            $retries = $this->table()->where('id', $payload->retryOf())->lockForUpdate()->value('retried_by');

            if (! $retries) {
                return;
            }

            $retries = $this->updateRetryStatus(
                $payload, json_decode($retries, true), $failed,
            );

            $this->table()->where('id', $payload->retryOf())->update([
                'retried_by' => json_encode($retries),
            ]);
        });
    }

    /**
     * Update the retry status of a job in a retry array.
     *
     * @param  array<int, array<string, mixed>>  $retries
     * @return array<int, array<string, mixed>>
     */
    protected function updateRetryStatus(JobPayload $payload, array $retries, bool $failed): array
    {
        return collect($retries)->map(function (array $retry) use ($payload, $failed) {
            return $retry['id'] === $payload->id()
                    ? Arr::set($retry, 'status', $failed ? 'failed' : 'completed')
                    : $retry;
        })->all();
    }

    /**
     * Delete the given monitored jobs by IDs.
     *
     * @param  array<int, mixed>  $ids
     */
    public function deleteMonitored(array $ids): void
    {
        $this->table()
            ->whereIn('id', array_map('strval', $ids))
            ->where('monitored', true)
            ->delete();
    }

    /**
     * Trim the recent job list.
     */
    public function trimRecentJobs(): void
    {
        $query = $this->table()
            ->where('status', '!=', 'failed')
            ->where('monitored', false)
            ->where('created_at', '<', $this->cutoffTime($this->recentJobExpires));

        do {
            $deleted = $query->limit(1000)->delete();
        } while ($deleted !== 0);
    }

    /**
     * Trim the failed job list.
     */
    public function trimFailedJobs(): void
    {
        $query = $this->table()
            ->where('status', 'failed')
            ->where('failed_at', '<', $this->cutoffTime($this->failedJobExpires));

        do {
            $deleted = $query->limit(1000)->delete();
        } while ($deleted !== 0);
    }

    /**
     * Trim the monitored job list.
     */
    public function trimMonitoredJobs(): void
    {
        $query = $this->table()
            ->where('monitored', true)
            ->where('completed_at', '<', $this->cutoffTime($this->monitoredJobExpires));

        do {
            $deleted = $query->limit(1000)->delete();
        } while ($deleted !== 0);
    }

    /**
     * Find a failed job by ID.
     */
    public function findFailed(mixed $id): ?stdClass
    {
        $record = $this->table()->where('id', (string) $id)->first();

        if (! $record || $record->status !== 'failed') {
            return null;
        }

        return $this->fromRecord($record);
    }

    /**
     * Mark the job as failed.
     *
     * @param  Throwable  $exception
     */
    public function failed(mixed $exception, mixed $connection, mixed $queue, JobPayload $payload): void
    {
        $time = $this->microtime();

        $this->table()->upsert([
            'id' => $payload->id(),
            'connection' => $connection,
            'queue' => $queue,
            'name' => $payload->decoded['displayName'],
            'status' => 'failed',
            'payload' => $payload->value,
            'exception' => (string) $exception,
            'context' => method_exists($exception, 'context')
                ? json_encode($exception->context())
                : null,
            'failed_at' => $time,
            'created_at' => $time,
            'updated_at' => $time,
        ], ['id'], [
            'connection', 'queue', 'name', 'status', 'payload',
            'exception', 'context', 'failed_at', 'updated_at',
        ]);
    }

    /**
     * Store the retry job ID on the original job record.
     */
    public function storeRetryReference(mixed $id, mixed $retryId): void
    {
        $this->connection()->transaction(function () use ($id, $retryId) {
            $retries = json_decode(
                $this->table()->where('id', $id)->lockForUpdate()->value('retried_by') ?: '[]', true,
            );

            $retries[] = [
                'id' => $retryId,
                'status' => 'pending',
                'retried_at' => CarbonImmutable::now()->getTimestamp(),
            ];

            $this->table()->where('id', $id)->update([
                'retried_by' => json_encode($retries),
            ]);
        });
    }

    /**
     * Delete a failed job by ID.
     */
    public function deleteFailed(mixed $id): int
    {
        return $this->table()
            ->where('id', (string) $id)
            ->where('status', 'failed')
            ->delete();
    }

    /**
     * Delete pending and reserved jobs for a queue.
     */
    public function purge(mixed $queue): int
    {
        return $this->table()
            ->where('queue', $queue)
            ->whereIn('status', ['pending', 'reserved'])
            ->delete();
    }

    /**
     * Get the current microtime as a string with microsecond precision.
     */
    protected function microtime(): string
    {
        return str_replace(',', '.', (string) microtime(true));
    }

    /**
     * Get a query builder for the horizon jobs table.
     */
    protected function table(): Builder
    {
        return $this->connection()->table('horizon_jobs');
    }

    /**
     * Get the database connection instance.
     */
    protected function connection(): ConnectionInterface
    {
        return $this->resolver->connection(config('horizon-db-driver.connection'));
    }
}
