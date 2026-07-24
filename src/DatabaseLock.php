<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder;
use Laravel\Horizon\Lock;

class DatabaseLock extends Lock
{
    /**
     * The database connection resolver instance.
     */
    public ConnectionResolverInterface $resolver;

    /**
     * Create a Horizon database lock manager.
     */
    public function __construct(ConnectionResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Determine if a lock exists for the given key.
     *
     * @param  string  $key
     */
    public function exists(mixed $key): bool
    {
        return $this->table()
            ->where('key', $key)
            ->where('expires_at', '>', CarbonImmutable::now()->getTimestamp())
            ->exists();
    }

    /**
     * Attempt to get a lock for the given key.
     *
     * @param  string  $key
     * @param  int  $seconds
     */
    public function get(mixed $key, mixed $seconds = 60): bool
    {
        $this->prune();

        return $this->table()->insertOrIgnore([
            'key' => $key,
            'expires_at' => CarbonImmutable::now()->addSeconds($seconds)->getTimestamp(),
        ]) === 1;
    }

    /**
     * Release the lock for the given key.
     *
     * @param  string  $key
     */
    public function release(mixed $key): void
    {
        $this->table()->where('key', $key)->delete();
    }

    /**
     * Remove any expired lock records.
     */
    protected function prune(): void
    {
        $this->table()
            ->where('expires_at', '<=', CarbonImmutable::now()->getTimestamp())
            ->delete();
    }

    /**
     * Get a query builder for the locks table.
     */
    protected function table(): Builder
    {
        return $this->dbConnection()->table('horizon_locks');
    }

    /**
     * Get the database connection instance.
     */
    protected function dbConnection(): ConnectionInterface
    {
        return $this->resolver->connection(
            config('horizon-db-driver.connection'),
        );
    }
}
