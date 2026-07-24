<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Connectors;

use HorizonDbDriver\HorizonDbDriver\DatabaseQueue;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Queue\Connectors\ConnectorInterface;
use RuntimeException;

class DatabaseConnector implements ConnectorInterface
{
    /**
     * Database connections.
     */
    protected ConnectionResolverInterface $connections;

    /**
     * Create a new connector instance.
     */
    public function __construct(ConnectionResolverInterface $connections)
    {
        $this->connections = $connections;
    }

    /**
     * Establish a queue connection.
     *
     * @param  array<string, mixed>  $config
     */
    public function connect(array $config): DatabaseQueue
    {
        $connection = $this->connections->connection($config['connection'] ?? null);

        if (! $connection instanceof Connection) {
            throw new RuntimeException('The horizon-db-driver queue connector requires a Illuminate\Database\Connection instance.');
        }

        return new DatabaseQueue(
            $connection,
            $config['table'],
            $config['queue'],
            $config['retry_after'] ?? 60,
            $config['after_commit'] ?? null,
        );
    }
}
