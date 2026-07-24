<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder;
use Laravel\Horizon\Contracts\TagRepository;

class DatabaseTagRepository implements TagRepository
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
     * Get the currently monitored tags.
     *
     * @return array<int, string>
     */
    public function monitoring(): array
    {
        return $this->monitoredTable()->pluck('tag')->all();
    }

    /**
     * Return the tags which are being monitored.
     *
     * @param  array<int, string>  $tags
     * @return array<int, string>
     */
    public function monitored(array $tags): array
    {
        return array_intersect($tags, $this->monitoring());
    }

    /**
     * Start monitoring the given tag.
     */
    public function monitor(mixed $tag): void
    {
        $this->monitoredTable()->updateOrInsert(['tag' => $tag], ['tag' => $tag]);
    }

    /**
     * Stop monitoring the given tag.
     */
    public function stopMonitoring(mixed $tag): void
    {
        $this->monitoredTable()->where('tag', $tag)->delete();
    }

    /**
     * Store the tags for the given job.
     *
     * @param  array<int, string>  $tags
     */
    public function add(mixed $id, array $tags): void
    {
        $this->insertTagRows($id, $tags, null);
    }

    /**
     * Store the tags for the given job temporarily.
     *
     * @param  array<int, string>  $tags
     */
    public function addTemporary(mixed $minutes, mixed $id, array $tags): void
    {
        $this->insertTagRows(
            $id, $tags, CarbonImmutable::now()->addMinutes($minutes)->getTimestamp(),
        );
    }

    /**
     * Insert the given tag rows for the given job.
     *
     * @param  array<int, string>  $tags
     */
    protected function insertTagRows(mixed $id, array $tags, ?int $expiresAt): void
    {
        if (empty($tags)) {
            return;
        }

        $time = str_replace(',', '.', (string) microtime(true));

        $rows = array_map(function (mixed $tag) use ($id, $time, $expiresAt) {
            return [
                'tag' => $tag,
                'job_id' => (string) $id,
                'created_at' => $time,
                'expires_at' => $expiresAt,
            ];
        }, array_values(array_unique($tags)));

        $this->table()->upsert($rows, ['tag', 'job_id'], ['created_at', 'expires_at']);
    }

    /**
     * Get the number of jobs matching a given tag.
     */
    public function count(mixed $tag): int
    {
        return $this->activeTagQuery($tag)->count();
    }

    /**
     * Get all of the job IDs for a given tag.
     *
     * @return array<int, string>
     */
    public function jobs(mixed $tag): array
    {
        return $this->activeTagQuery($tag)
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('job_id')
            ->all();
    }

    /**
     * Paginate the job IDs for a given tag.
     *
     * @return array<int, string>
     */
    public function paginate(mixed $tag, mixed $startingAt = 0, mixed $limit = 25): array
    {
        $ids = $this->activeTagQuery($tag)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->offset($startingAt)
            ->limit($limit)
            ->pluck('job_id')
            ->all();

        return collect($ids)->mapWithKeys(function (mixed $id, mixed $index) use ($startingAt) {
            return [$index + $startingAt => $id];
        })->all();
    }

    /**
     * Get a query builder filtered to non-expired rows for the given tag.
     */
    protected function activeTagQuery(mixed $tag): Builder
    {
        $now = CarbonImmutable::now()->getTimestamp();

        return $this->table()
            ->where('tag', $tag)
            ->where(function (Builder $query) use ($now) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
            });
    }

    /**
     * Remove the given job IDs from the given tag.
     *
     * @param  array<int, string>|string  $tags
     * @param  array<int, string>|string  $ids
     */
    public function forgetJobs(mixed $tags, mixed $ids): void
    {
        $this->table()
            ->whereIn('tag', (array) $tags)
            ->whereIn('job_id', array_map('strval', (array) $ids))
            ->delete();
    }

    /**
     * Delete the given tag from storage.
     */
    public function forget(mixed $tag): void
    {
        $this->table()->where('tag', $tag)->delete();
    }

    /**
     * Trim expired tag entries from storage.
     */
    public function trimExpired(): void
    {
        $query = $this->table()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', CarbonImmutable::now()->getTimestamp());

        do {
            $deleted = $query->limit(1000)->delete();
        } while ($deleted !== 0);
    }

    /**
     * Get a query builder for the horizon tags table.
     */
    protected function table(): Builder
    {
        return $this->connection()->table('horizon_tags');
    }

    /**
     * Get a query builder for the horizon monitored tags table.
     */
    protected function monitoredTable(): Builder
    {
        return $this->connection()->table('horizon_monitored_tags');
    }

    /**
     * Get the database connection instance.
     */
    protected function connection(): ConnectionInterface
    {
        return $this->resolver->connection(config('horizon-db-driver.connection'));
    }
}
