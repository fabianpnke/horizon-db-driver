<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Tests\Feature;

use HorizonDbDriver\HorizonDbDriver\Tests\TestCase;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Horizon\Contracts\HorizonCommandQueue;

class DatabaseHorizonCommandQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_pushes_and_pops_pending_commands_for_a_queue_name(): void
    {
        $queue = $this->app->make(HorizonCommandQueue::class);

        $queue->push('supervisor-1', 'scale', ['size' => 3]);
        $queue->push('supervisor-1', 'pause', []);
        $queue->push('supervisor-2', 'terminate', []);

        $pending = $queue->pending('supervisor-1');

        $this->assertCount(2, $pending);
        $this->assertSame('scale', $pending[0]->command);
        $this->assertSame(['size' => 3], $pending[0]->options);
        $this->assertSame('pause', $pending[1]->command);

        $this->assertEmpty($queue->pending('supervisor-1'));
        $this->assertCount(1, $queue->pending('supervisor-2'));
    }

    public function test_it_does_not_start_a_transaction_when_no_commands_are_pending(): void
    {
        $queue = $this->app->make(HorizonCommandQueue::class);

        $transactions = 0;

        $this->app['events']->listen(TransactionBeginning::class, function () use (&$transactions): void {
            $transactions++;
        });

        $this->assertEmpty($queue->pending('supervisor-1'));
        $this->assertSame(0, $transactions);

        $queue->push('supervisor-1', 'scale', ['size' => 3]);

        $this->assertCount(1, $queue->pending('supervisor-1'));
        $this->assertSame(1, $transactions);
    }

    public function test_it_flushes_the_command_queue_for_a_given_name(): void
    {
        $queue = $this->app->make(HorizonCommandQueue::class);

        $queue->push('supervisor-1', 'scale', ['size' => 3]);
        $queue->flush('supervisor-1');

        $this->assertEmpty($queue->pending('supervisor-1'));
    }
}
