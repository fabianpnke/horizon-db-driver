<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Concerns;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Throwable;

trait InteractsWithTransactions
{
    /**
     * Get the database connection instance.
     */
    abstract protected function connection(): ConnectionInterface;

    /**
     * Execute the given callback within a transaction.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    protected function transaction(Closure $callback): mixed
    {
        $connection = $this->connection();

        $this->rollBackOrphanedTransaction($connection);

        return $connection->transaction($callback);
    }

    /**
     * Roll back a transaction the connection is no longer aware of.
     *
     * PDO keeps its own "in transaction" flag, and a commit or rollback that
     * fails leaves that flag set even though Laravel has already reset its
     * own transaction counter. Horizon's supervisors are long lived and
     * reuse a single connection, so once the two disagree every later
     * transaction throws "There is already an active transaction" until the
     * process is restarted.
     */
    protected function rollBackOrphanedTransaction(ConnectionInterface $connection): void
    {
        if (! $connection instanceof Connection || $connection->transactionLevel() > 0) {
            return;
        }

        if (! $connection->getPdo()->inTransaction()) {
            return;
        }

        try {
            if ($connection->getPdo()->rollBack()) {
                return;
            }
        } catch (Throwable) {
            // Handled below.
        }

        // The rollback fails when the underlying connection is already gone.
        // Discarding the connection leaves the next transaction to
        // reconnect with a PDO instance we can begin one on.
        $connection->disconnect();
    }
}
