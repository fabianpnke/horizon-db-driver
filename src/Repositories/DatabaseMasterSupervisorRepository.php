<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\MasterSupervisor;
use stdClass;

class DatabaseMasterSupervisorRepository implements MasterSupervisorRepository
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
     * Get the names of all the master supervisors currently running.
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
     * Get information on all of the master supervisors.
     *
     * @return array<int, stdClass>
     */
    public function all(): array
    {
        return $this->get($this->names());
    }

    /**
     * Get information on a master supervisor by name.
     */
    // @phpstan-ignore method.childReturnType (Contracts\MasterSupervisorRepository::find() is documented as `@return array`, but Horizon's own RedisMasterSupervisorRepository::find() returns stdClass|null too — the interface docblock is inaccurate for every implementation, not just this one)
    public function find(mixed $name): ?stdClass
    {
        return Arr::first($this->get([$name]));
    }

    /**
     * Get information on the given master supervisors.
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
                    'pid' => $record->pid,
                    'status' => $record->status,
                    'supervisors' => json_decode($record->supervisors, true),
                ];
            })->all();
    }

    /**
     * Update the information about the given master supervisor.
     */
    public function update(MasterSupervisor $master): void
    {
        $supervisors = $master->supervisors->map->name->all();

        $this->table()->updateOrInsert(['name' => $master->name], [
            'pid' => $master->pid(),
            'status' => $master->working ? 'running' : 'paused',
            'supervisors' => json_encode($supervisors),
            'expires_at' => CarbonImmutable::now()->addSeconds(15)->getTimestamp(),
            'updated_at' => CarbonImmutable::now()->getTimestamp(),
        ]);
    }

    /**
     * Remove the master supervisor information from storage.
     */
    public function forget(mixed $name): void
    {
        if (! $master = $this->find($name)) {
            return;
        }

        app(SupervisorRepository::class)->forget(
            $master->supervisors,
        );

        $this->table()->where('name', $name)->delete();
    }

    /**
     * Remove expired master supervisors from storage.
     */
    public function flushExpired(): void
    {
        $this->table()
            ->where('expires_at', '<', CarbonImmutable::now()->getTimestamp())
            ->delete();
    }

    /**
     * Get a query builder for the horizon master supervisors table.
     */
    protected function table(): Builder
    {
        return $this->connection()->table('horizon_master_supervisors');
    }

    /**
     * Get the database connection instance.
     */
    protected function connection(): ConnectionInterface
    {
        return $this->resolver->connection(config('horizon-db-driver.connection'));
    }
}
