<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Supervisor;
use stdClass;

class DatabaseSupervisorRepository implements SupervisorRepository
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
     * Get the names of all the supervisors currently running.
     *
     * @return array<int, string>
     */
    public function names(): array
    {
        return $this->table()
            ->where('expires_at', '>=', CarbonImmutable::now()->getTimestamp())
            ->orderBy('updated_at', 'desc')
            ->pluck('name')
            ->all();
    }

    /**
     * Get information on all of the supervisors.
     *
     * @return array<int, stdClass>
     */
    public function all(): array
    {
        return $this->get($this->names());
    }

    /**
     * Get information on a supervisor by name.
     */
    // @phpstan-ignore method.childReturnType (Contracts\SupervisorRepository::find() is documented as `@return array`, but Horizon's own RedisSupervisorRepository::find() returns stdClass|null too — the interface docblock is inaccurate for every implementation, not just this one)
    public function find(mixed $name): ?stdClass
    {
        return Arr::first($this->get([$name]));
    }

    /**
     * Get information on the given supervisors.
     *
     * @param  array<int, string>  $names
     * @return array<int, stdClass>
     */
    public function get(array $names): array
    {
        if (empty($names)) {
            return [];
        }

        return $this->table()
            ->whereIn('name', $names)
            ->get()
            ->map(function (stdClass $record) {
                return (object) [
                    'name' => $record->name,
                    'master' => $record->master,
                    'pid' => $record->pid,
                    'status' => $record->status,
                    'processes' => json_decode($record->processes, true),
                    'options' => json_decode($record->options, true),
                ];
            })->all();
    }

    /**
     * Get the longest active timeout setting for a supervisor.
     */
    public function longestActiveTimeout(): int
    {
        return collect($this->all())->max(function (stdClass $supervisor) {
            return $supervisor->options['timeout'];
        }) ?: 0;
    }

    /**
     * Update the information about the given supervisor process.
     */
    public function update(Supervisor $supervisor): void
    {
        $processes = $supervisor->processPools->mapWithKeys(function (mixed $pool) use ($supervisor) {
            return [$supervisor->options->connection.':'.$pool->queue() => count($pool->processes())];
        })->toJson();

        $this->table()->updateOrInsert(['name' => $supervisor->name], [
            'master' => implode(':', explode(':', $supervisor->name, -1)),
            'pid' => $supervisor->pid(),
            'status' => $supervisor->working ? 'running' : 'paused',
            'processes' => $processes,
            'options' => $supervisor->options->toJson(),
            'expires_at' => CarbonImmutable::now()->addSeconds(30)->getTimestamp(),
            'updated_at' => CarbonImmutable::now()->getTimestamp(),
        ]);
    }

    /**
     * Remove the supervisor information from storage.
     *
     * @param  array<int, string>|string  $names
     */
    public function forget(mixed $names): void
    {
        $names = array_map('strval', (array) $names);

        if (empty($names)) {
            return;
        }

        $this->table()->whereIn('name', $names)->delete();
    }

    /**
     * Remove expired supervisors from storage.
     */
    public function flushExpired(): void
    {
        $this->table()
            ->where('expires_at', '<', CarbonImmutable::now()->getTimestamp())
            ->delete();
    }

    /**
     * Get a query builder for the horizon supervisors table.
     */
    protected function table(): Builder
    {
        return $this->connection()->table('horizon_supervisors');
    }

    /**
     * Get the database connection instance.
     */
    protected function connection(): ConnectionInterface
    {
        return $this->resolver->connection(config('horizon-db-driver.connection'));
    }
}
