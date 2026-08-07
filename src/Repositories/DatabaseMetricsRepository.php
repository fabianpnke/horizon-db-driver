<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Repositories;

use Carbon\CarbonImmutable;
use HorizonDbDriver\HorizonDbDriver\Concerns\InteractsWithTransactions;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Lock;
use Laravel\Horizon\WaitTimeCalculator;
use stdClass;

class DatabaseMetricsRepository implements MetricsRepository
{
    use InteractsWithTransactions;

    /**
     * The database connection resolver instance.
     */
    public ConnectionResolverInterface $resolver;

    /**
     * Create a new repository instance.
     */
    public function __construct(ConnectionResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Get all of the class names that have metrics measurements.
     *
     * @return array<int, string>
     */
    public function measuredJobs(): array
    {
        return $this->measuredKeysFor('job', '/job:(.*)$/');
    }

    /**
     * Get all of the queues that have metrics measurements.
     *
     * @return array<int, string>
     */
    public function measuredQueues(): array
    {
        return $this->measuredKeysFor('queue', '/queue:(.*)$/');
    }

    /**
     * Get the distinct measured keys of the given type from the metrics and snapshots tables.
     *
     * @return array<int, string>
     */
    protected function measuredKeysFor(string $type, string $pattern): array
    {
        $keys = $this->metricsTable()->where('type', $type)->pluck('key')
            ->merge($this->snapshotsTable()->where('type', $type)->distinct()->pluck('key'))
            ->unique();

        return $keys->map(function (mixed $key) use ($pattern) {
            return preg_match($pattern, $key, $matches) ? $matches[1] : $key;
        })->sort()->values()->all();
    }

    /**
     * Get the jobs processed per minute since the last snapshot.
     */
    // @phpstan-ignore method.childReturnType (Contracts\MetricsRepository::jobsProcessedPerMinute() is documented as `@return int`, but `round()` returns float and Horizon's own RedisMetricsRepository::jobsProcessedPerMinute() returns the same unrounded-to-int float — the interface docblock is inaccurate for every implementation, not just this one)
    public function jobsProcessedPerMinute(): float
    {
        return round($this->throughput() / $this->minutesSinceLastSnapshot());
    }

    /**
     * Get the application's total throughput since the last snapshot.
     */
    public function throughput(): int
    {
        return (int) $this->metricsTable()->where('type', 'queue')->sum('throughput');
    }

    /**
     * Get the throughput for a given job.
     */
    public function throughputForJob(mixed $job): int
    {
        return $this->throughputFor('job:'.$job);
    }

    /**
     * Get the throughput for a given queue.
     */
    public function throughputForQueue(mixed $queue): int
    {
        return $this->throughputFor('queue:'.$queue);
    }

    /**
     * Get the throughput for a given key.
     */
    protected function throughputFor(string $key): int
    {
        return (int) $this->metricsTable()->where('key', $key)->value('throughput');
    }

    /**
     * Get the average runtime for a given job in milliseconds.
     */
    public function runtimeForJob(mixed $job): float
    {
        return $this->runtimeFor('job:'.$job);
    }

    /**
     * Get the average runtime for a given queue in milliseconds.
     */
    public function runtimeForQueue(mixed $queue): float
    {
        return $this->runtimeFor('queue:'.$queue);
    }

    /**
     * Get the average runtime for a given key in milliseconds.
     */
    protected function runtimeFor(string $key): float
    {
        return (float) $this->metricsTable()->where('key', $key)->value('runtime');
    }

    /**
     * Get the queue that has the longest runtime.
     */
    // @phpstan-ignore method.childReturnType (Contracts\MetricsRepository::queueWithMaximumRuntime() is documented as `@return int`, but this returns a queue name — Horizon's own RedisMetricsRepository::queueWithMaximumRuntime() has the identical string|null-vs-int docblock mismatch)
    public function queueWithMaximumRuntime(): ?string
    {
        return collect($this->measuredQueues())->sortBy(function (mixed $queue) {
            $snapshot = $this->latestSnapshotFor('queue:'.$queue);

            return $snapshot ? $snapshot->runtime : 0;
        })->last();
    }

    /**
     * Get the queue that has the most throughput.
     */
    // @phpstan-ignore method.childReturnType (Contracts\MetricsRepository::queueWithMaximumThroughput() is documented as `@return int`, but this returns a queue name — Horizon's own RedisMetricsRepository::queueWithMaximumThroughput() has the identical string|null-vs-int docblock mismatch)
    public function queueWithMaximumThroughput(): ?string
    {
        return collect($this->measuredQueues())->sortBy(function (mixed $queue) {
            $snapshot = $this->latestSnapshotFor('queue:'.$queue);

            return $snapshot ? $snapshot->throughput : 0;
        })->last();
    }

    /**
     * Get the latest snapshot stored for the given key.
     */
    protected function latestSnapshotFor(string $key): ?stdClass
    {
        $record = $this->snapshotsTable()
            ->where('key', $key)
            ->orderBy('taken_at', 'desc')
            ->first();

        return $record ? (object) json_decode($record->snapshot, true) : null;
    }

    /**
     * Increment the metrics information for a job.
     */
    public function incrementJob(mixed $job, mixed $runtime): void
    {
        $this->incrementMetric('job:'.$job, 'job', $runtime);
    }

    /**
     * Increment the metrics information for a queue.
     */
    public function incrementQueue(mixed $queue, mixed $runtime): void
    {
        $this->incrementMetric('queue:'.$queue, 'queue', $runtime);
    }

    /**
     * Increment the metric counters for a key, recomputing the running average runtime.
     */
    protected function incrementMetric(string $key, string $type, float $runtime): void
    {
        $safeRuntime = number_format($runtime, 6, '.', '');

        $this->metricsTable()->upsert(
            [[
                'key' => $key,
                'type' => $type,
                'throughput' => 1,
                'runtime' => $runtime,
            ]],
            ['key'],
            [
                // @phpstan-ignore-next-line argument.type ($safeRuntime is always a number_format() output, so this is a fixed-format numeric string, not attacker-controlled input — DB::raw() has no parameter-binding form for a computed upsert column)
                'runtime' => DB::raw("(horizon_metrics.runtime * horizon_metrics.throughput + {$safeRuntime}) / (horizon_metrics.throughput + 1)"),
                'throughput' => DB::raw('horizon_metrics.throughput + 1'),
            ],
        );
    }

    /**
     * Get all of the snapshots for the given job.
     *
     * @return array<int, stdClass>
     */
    public function snapshotsForJob(mixed $job): array
    {
        return $this->snapshotsFor('job:'.$job);
    }

    /**
     * Get all of the snapshots for the given queue.
     *
     * @return array<int, stdClass>
     */
    public function snapshotsForQueue(mixed $queue): array
    {
        return $this->snapshotsFor('queue:'.$queue);
    }

    /**
     * Get all of the snapshots for the given key.
     *
     * @return array<int, stdClass>
     */
    protected function snapshotsFor(string $key): array
    {
        return $this->snapshotsTable()
            ->where('key', $key)
            ->orderBy('taken_at')
            ->pluck('snapshot')
            ->map(function (mixed $snapshot) {
                return (object) json_decode($snapshot, true);
            })->values()->all();
    }

    /**
     * Store a snapshot of the metrics information.
     */
    public function snapshot(): void
    {
        collect($this->measuredJobs())->each(function (mixed $job) {
            $this->storeSnapshotForJob($job);
        });

        collect($this->measuredQueues())->each(function (mixed $queue) {
            $this->storeSnapshotForQueue($queue);
        });

        $this->storeSnapshotTimestamp();
    }

    /**
     * Store a snapshot for the given job.
     */
    protected function storeSnapshotForJob(string $job): void
    {
        $data = $this->baseSnapshotData($key = 'job:'.$job, 'job');

        $this->snapshotsTable()->insert([
            'key' => $key,
            'type' => 'job',
            'taken_at' => $time = CarbonImmutable::now()->getTimestamp(),
            'snapshot' => json_encode([
                'throughput' => $data['throughput'],
                'runtime' => $data['runtime'],
                'time' => $time,
            ]),
        ]);

        $this->trimSnapshots($key, config('horizon.metrics.trim_snapshots.job', 24));
    }

    /**
     * Store a snapshot for the given queue.
     */
    protected function storeSnapshotForQueue(string $queue): void
    {
        $data = $this->baseSnapshotData($key = 'queue:'.$queue, 'queue');

        $this->snapshotsTable()->insert([
            'key' => $key,
            'type' => 'queue',
            'taken_at' => $time = CarbonImmutable::now()->getTimestamp(),
            'snapshot' => json_encode([
                'throughput' => $data['throughput'],
                'runtime' => $data['runtime'],
                'wait' => app(WaitTimeCalculator::class)->calculateFor($queue),
                'time' => $time,
            ]),
        ]);

        $this->trimSnapshots($key, config('horizon.metrics.trim_snapshots.queue', 24));
    }

    /**
     * Trim the snapshots stored for a given key.
     */
    protected function trimSnapshots(string $key, int $keep): void
    {
        $keep = max(1, $keep);

        $ids = $this->snapshotsTable()
            ->where('key', $key)
            ->orderBy('taken_at', 'desc')
            ->offset($keep)
            ->limit(1000)
            ->pluck('id')
            ->all();

        if (! empty($ids)) {
            $this->snapshotsTable()->whereIn('id', $ids)->delete();
        }
    }

    /**
     * Get the base snapshot data for a given key, resetting its counters.
     *
     * @return array<string, int|float>
     */
    protected function baseSnapshotData(string $key, string $type): array
    {
        return $this->transaction(function () use ($key) {
            $record = $this->metricsTable()->where('key', $key)->lockForUpdate()->first();

            $data = [
                'throughput' => $record ? (int) $record->throughput : 0,
                'runtime' => $record ? (float) $record->runtime : 0.0,
            ];

            if ($record) {
                $this->metricsTable()->where('key', $key)->delete();
            }

            return $data;
        });
    }

    /**
     * Get the number of minutes passed since the last snapshot.
     */
    protected function minutesSinceLastSnapshot(): float
    {
        $lastSnapshotAt = (int) ($this->metaTable()->where('key', 'last_snapshot_at')->value('value')
                                    ?: $this->storeSnapshotTimestamp());

        return max(
            (CarbonImmutable::now()->getTimestamp() - $lastSnapshotAt) / 60, 1,
        );
    }

    /**
     * Store the current timestamp as the "last snapshot timestamp".
     */
    protected function storeSnapshotTimestamp(): int
    {
        return tap(CarbonImmutable::now()->getTimestamp(), function (int $timestamp) {
            $this->metaTable()->updateOrInsert(
                ['key' => 'last_snapshot_at'], ['value' => (string) $timestamp],
            );
        });
    }

    /**
     * Attempt to acquire a lock to monitor the queue wait times.
     */
    public function acquireWaitTimeMonitorLock(): bool
    {
        return app(Lock::class)->get('monitor:time-to-clear');
    }

    /**
     * Clear the metrics for a key.
     */
    public function forget(mixed $key): void
    {
        $this->metricsTable()->where('key', $key)->delete();
        $this->snapshotsTable()->where('key', $key)->delete();
    }

    /**
     * Delete all stored metrics information.
     */
    public function clear(): void
    {
        $this->metricsTable()->delete();
        $this->snapshotsTable()->delete();
        $this->metaTable()->delete();
    }

    /**
     * Get a query builder for the horizon metrics table.
     */
    protected function metricsTable(): Builder
    {
        return $this->connection()->table('horizon_metrics');
    }

    /**
     * Get a query builder for the horizon metric snapshots table.
     */
    protected function snapshotsTable(): Builder
    {
        return $this->connection()->table('horizon_metric_snapshots');
    }

    /**
     * Get a query builder for the horizon metric meta table.
     */
    protected function metaTable(): Builder
    {
        return $this->connection()->table('horizon_metric_meta');
    }

    /**
     * Get the database connection instance.
     */
    public function connection(): ConnectionInterface
    {
        return $this->resolver->connection(config('horizon-db-driver.connection'));
    }
}
