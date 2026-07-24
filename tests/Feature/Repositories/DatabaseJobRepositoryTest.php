<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Tests\Feature\Repositories;

use HorizonDbDriver\HorizonDbDriver\Repositories\DatabaseJobRepository;
use HorizonDbDriver\HorizonDbDriver\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\JobPayload;

class DatabaseJobRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_storing_multiple_retry_references_accumulates_them_instead_of_overwriting(): void
    {
        DB::table('horizon_jobs')->insert([
            'id' => 'parent-1',
            'connection' => 'database',
            'queue' => 'default',
            'name' => 'App\\Jobs\\ExampleJob',
            'status' => 'failed',
            'payload' => '{}',
            'created_at' => microtime(true),
            'updated_at' => microtime(true),
        ]);

        $jobs = $this->app->make(DatabaseJobRepository::class);

        $jobs->storeRetryReference('parent-1', 'retry-1');
        $jobs->storeRetryReference('parent-1', 'retry-2');

        $retriedBy = json_decode(DB::table('horizon_jobs')->where('id', 'parent-1')->value('retried_by'), true);

        $this->assertCount(2, $retriedBy);
        $this->assertSame(['retry-1', 'retry-2'], array_column($retriedBy, 'id'));
    }

    public function test_completing_a_retry_updates_only_its_own_entry_on_the_parent(): void
    {
        DB::table('horizon_jobs')->insert([
            'id' => 'parent-1',
            'connection' => 'database',
            'queue' => 'default',
            'name' => 'App\\Jobs\\ExampleJob',
            'status' => 'failed',
            'payload' => '{}',
            'retried_by' => json_encode([
                ['id' => 'retry-1', 'status' => 'pending', 'retried_at' => now()->getTimestamp()],
                ['id' => 'retry-2', 'status' => 'pending', 'retried_at' => now()->getTimestamp()],
            ]),
            'created_at' => microtime(true),
            'updated_at' => microtime(true),
        ]);

        DB::table('horizon_jobs')->insert([
            'id' => 'retry-2',
            'connection' => 'database',
            'queue' => 'default',
            'name' => 'App\\Jobs\\ExampleJob',
            'status' => 'reserved',
            'payload' => '{}',
            'created_at' => microtime(true),
            'updated_at' => microtime(true),
        ]);

        $jobs = $this->app->make(DatabaseJobRepository::class);

        $payload = new JobPayload(json_encode([
            'uuid' => 'retry-2',
            'retry_of' => 'parent-1',
            'displayName' => 'App\\Jobs\\ExampleJob',
        ]));

        $jobs->completed($payload, failed: false);

        $retriedBy = collect(json_decode(DB::table('horizon_jobs')->where('id', 'parent-1')->value('retried_by'), true))
            ->keyBy('id');

        $this->assertSame('completed', $retriedBy['retry-2']['status']);
        $this->assertSame('pending', $retriedBy['retry-1']['status']);
    }
}
