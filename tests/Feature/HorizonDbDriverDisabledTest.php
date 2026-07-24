<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Tests\Feature;

use HorizonDbDriver\HorizonDbDriver\Tests\TestCase;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Repositories\RedisJobRepository;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use PHPUnit\Framework\Attributes\Test;

class HorizonDbDriverDisabledTest extends TestCase
{
    #[Test]
    #[DefineEnvironment('disableHorizonDbDriver')]
    public function it_leaves_horizon_on_its_default_redis_bindings_when_disabled(): void
    {
        $this->assertInstanceOf(RedisJobRepository::class, $this->app->make(JobRepository::class));
    }
}
