<?php

declare(strict_types=1);

use Laravel\Horizon\Contracts\HorizonCommandQueue;

it('pushes and pops pending commands for a queue name', function () {
    $queue = app(HorizonCommandQueue::class);

    $queue->push('supervisor-1', 'scale', ['size' => 3]);
    $queue->push('supervisor-1', 'pause', []);
    $queue->push('supervisor-2', 'terminate', []);

    $pending = $queue->pending('supervisor-1');

    expect($pending)->toHaveCount(2);
    expect($pending[0]->command)->toBe('scale');
    expect($pending[0]->options)->toBe(['size' => 3]);
    expect($pending[1]->command)->toBe('pause');

    expect($queue->pending('supervisor-1'))->toBeEmpty();
    expect($queue->pending('supervisor-2'))->toHaveCount(1);
});

it('flushes the command queue for a given name', function () {
    $queue = app(HorizonCommandQueue::class);

    $queue->push('supervisor-1', 'scale', ['size' => 3]);
    $queue->flush('supervisor-1');

    expect($queue->pending('supervisor-1'))->toBeEmpty();
});
