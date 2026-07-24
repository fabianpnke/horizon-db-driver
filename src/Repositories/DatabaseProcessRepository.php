<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder;
use Laravel\Horizon\Contracts\ProcessRepository;

class DatabaseProcessRepository implements ProcessRepository
{
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
     * Get all of the orphan process IDs and the times they were observed.
     *
     * @return array<string, int>
     */
    public function allOrphans(mixed $master): array
    {
        return $this->table()
            ->where('master', $master)
            ->pluck('recorded_at', 'process_id')
            ->all();
    }

    /**
     * Record the given process IDs as orphaned.
     *
     * @param  array<int, mixed>  $processIds
     */
    // @phpstan-ignore method.childReturnType (Contracts\ProcessRepository::orphaned() is documented as `@return array`, but Horizon's own RedisProcessRepository::orphaned() is `@return void` too — the interface docblock is inaccurate for every implementation, not just this one)
    public function orphaned(mixed $master, array $processIds): void
    {
        $time = CarbonImmutable::now()->getTimestamp();

        $processIds = array_map('strval', $processIds);

        $existing = $this->table()
            ->where('master', $master)
            ->pluck('process_id')
            ->all();

        $shouldRemove = array_diff($existing, $processIds);

        if (! empty($shouldRemove)) {
            $this->table()
                ->where('master', $master)
                ->whereIn('process_id', $shouldRemove)
                ->delete();
        }

        $shouldInsert = array_diff($processIds, $existing);

        if (! empty($shouldInsert)) {
            $this->table()->insertOrIgnore(array_map(function (mixed $processId) use ($master, $time) {
                return [
                    'master' => $master,
                    'process_id' => $processId,
                    'recorded_at' => $time,
                ];
            }, array_values($shouldInsert)));
        }
    }

    /**
     * Get the process IDs orphaned for at least the given number of seconds.
     *
     * @return array<int, string>
     */
    public function orphanedFor(mixed $master, mixed $seconds): array
    {
        $expiresAt = CarbonImmutable::now()->getTimestamp() - $seconds;

        return $this->table()
            ->where('master', $master)
            ->where('recorded_at', '<', $expiresAt)
            ->orderBy('id')
            ->pluck('process_id')
            ->all();
    }

    /**
     * Remove the given process IDs from the orphan list.
     *
     * @param  array<int, mixed>  $processIds
     */
    public function forgetOrphans(mixed $master, array $processIds): void
    {
        $this->table()
            ->where('master', $master)
            ->whereIn('process_id', array_map('strval', $processIds))
            ->delete();
    }

    /**
     * Get a query builder for the horizon processes table.
     */
    protected function table(): Builder
    {
        return $this->connection()->table('horizon_processes');
    }

    /**
     * Get the database connection instance.
     */
    protected function connection(): ConnectionInterface
    {
        return $this->resolver->connection(config('horizon-db-driver.connection'));
    }
}
