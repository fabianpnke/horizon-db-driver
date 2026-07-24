<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Tests\Feature;

use HorizonDbDriver\HorizonDbDriver\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Horizon\Contracts\HorizonCommandQueue;
use PHPUnit\Framework\Attributes\Test;

class DatabaseHorizonCommandQueueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_pushes_and_pops_pending_commands_for_a_queue_name(): void
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

    #[Test]
    public function it_flushes_the_command_queue_for_a_given_name(): void
    {
        $queue = $this->app->make(HorizonCommandQueue::class);

        $queue->push('supervisor-1', 'scale', ['size' => 3]);
        $queue->flush('supervisor-1');

        $this->assertEmpty($queue->pending('supervisor-1'));
    }
}
