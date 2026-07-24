<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Tests\Feature\Repositories;

use HorizonDbDriver\HorizonDbDriver\Repositories\DatabaseSupervisorRepository;
use HorizonDbDriver\HorizonDbDriver\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class DatabaseSupervisorRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stores_and_retrieves_supervisor_information(): void
    {
        DB::table('horizon_supervisors')->insert([
            'name' => 'horizon-1:supervisor-1',
            'master' => 'horizon-1',
            'pid' => 123,
            'status' => 'running',
            'processes' => json_encode(['redis:default' => 1]),
            'options' => json_encode(['timeout' => 60]),
            'expires_at' => now()->addSeconds(30)->getTimestamp(),
            'updated_at' => now()->getTimestamp(),
        ]);

        $supervisors = $this->app->make(DatabaseSupervisorRepository::class);

        $this->assertSame(['horizon-1:supervisor-1'], $supervisors->names());

        $found = $supervisors->find('horizon-1:supervisor-1');

        $this->assertSame('running', $found->status);
        $this->assertSame(['redis:default' => 1], $found->processes);
    }

    #[Test]
    public function it_removes_expired_supervisors_from_storage(): void
    {
        DB::table('horizon_supervisors')->insert([
            'name' => 'horizon-1:supervisor-1',
            'master' => 'horizon-1',
            'pid' => 123,
            'status' => 'running',
            'processes' => json_encode([]),
            'options' => json_encode([]),
            'expires_at' => now()->subSecond()->getTimestamp(),
            'updated_at' => now()->getTimestamp(),
        ]);

        $supervisors = $this->app->make(DatabaseSupervisorRepository::class);
        $supervisors->flushExpired();

        $this->assertFalse(DB::table('horizon_supervisors')->exists());
    }

    #[Test]
    public function it_forgets_a_supervisor_by_name(): void
    {
        DB::table('horizon_supervisors')->insert([
            'name' => 'horizon-1:supervisor-1',
            'master' => 'horizon-1',
            'pid' => 123,
            'status' => 'running',
            'processes' => json_encode([]),
            'options' => json_encode([]),
            'expires_at' => now()->addSeconds(30)->getTimestamp(),
            'updated_at' => now()->getTimestamp(),
        ]);

        $supervisors = $this->app->make(DatabaseSupervisorRepository::class);
        $supervisors->forget('horizon-1:supervisor-1');

        $this->assertFalse(DB::table('horizon_supervisors')->exists());
    }
}
