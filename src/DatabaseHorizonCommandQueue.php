<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder;
use Laravel\Horizon\Contracts\HorizonCommandQueue;
use stdClass;

class DatabaseHorizonCommandQueue implements HorizonCommandQueue
{
    /**
     * The database connection resolver instance.
     */
    public ConnectionResolverInterface $resolver;

    /**
     * Create a new command queue instance.
     */
    public function __construct(ConnectionResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Push a command onto a given queue.
     *
     * @param  string  $name
     * @param  string  $command
     * @param  array<string, mixed>  $options
     */
    public function push(mixed $name, mixed $command, array $options = []): void
    {
        $this->table()->insert([
            'name' => $name,
            'command' => $command,
            'options' => json_encode($options),
            'created_at' => str_replace(',', '.', (string) microtime(true)),
        ]);
    }

    /**
     * Get the pending commands for a given queue name.
     *
     * @param  string  $name
     * @return array<int, object{command: string, options: mixed}>
     */
    public function pending(mixed $name): array
    {
        return $this->connection()->transaction(function () use ($name) {
            $records = $this->table()
                ->where('name', $name)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($records->isEmpty()) {
                return [];
            }

            $this->table()->whereIn('id', $records->pluck('id')->all())->delete();

            return $records->map(function (stdClass $record) {
                return (object) [
                    'command' => $record->command,
                    'options' => json_decode((string) $record->options, true),
                ];
            })->all();
        });
    }

    /**
     * Flush the command queue for a given queue name.
     *
     * @param  string  $name
     */
    public function flush(mixed $name): void
    {
        $this->table()->where('name', $name)->delete();
    }

    /**
     * Get a query builder for the horizon command queue table.
     */
    protected function table(): Builder
    {
        return $this->connection()->table('horizon_command_queue');
    }

    /**
     * Get the database connection instance.
     */
    protected function connection(): ConnectionInterface
    {
        return $this->resolver->connection(config('horizon-db-driver.connection'));
    }
}
