<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Tests\Feature;

use HorizonDbDriver\HorizonDbDriver\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Contracts\HorizonCommandQueue;

class DatabaseHorizonCommandQueueRecoveryTest extends TestCase
{
    public function test_it_recovers_from_a_transaction_left_open_on_the_connection(): void
    {
        $queue = $this->app->make(HorizonCommandQueue::class);

        $queue->push('supervisor-1', 'scale', ['size' => 3]);

        // Mimic a commit that failed against the database: PDO keeps its own
        // transaction flag set while Laravel resets its transaction counter,
        // which makes every later transaction throw "There is already an
        // active transaction" for the life of the supervisor process.
        DB::getPdo()->beginTransaction();

        $this->assertSame(0, DB::transactionLevel());
        $this->assertTrue(DB::getPdo()->inTransaction());

        $pending = $queue->pending('supervisor-1');

        $this->assertCount(1, $pending);
        $this->assertSame('scale', $pending[0]->command);
        $this->assertSame(['size' => 3], $pending[0]->options);
        $this->assertEmpty($queue->pending('supervisor-1'));
    }
}
