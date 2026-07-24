<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Tests\Feature;

use HorizonDbDriver\HorizonDbDriver\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Horizon\Lock;
use PHPUnit\Framework\Attributes\Test;

class DatabaseLockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_acquires_and_releases_a_lock(): void
    {
        $lock = $this->app->make(Lock::class);

        $this->assertTrue($lock->get('horizon:test-lock'));
        $this->assertTrue($lock->exists('horizon:test-lock'));

        $lock->release('horizon:test-lock');

        $this->assertFalse($lock->exists('horizon:test-lock'));
    }

    #[Test]
    public function it_does_not_grant_a_lock_that_is_already_held(): void
    {
        $lock = $this->app->make(Lock::class);

        $this->assertTrue($lock->get('horizon:test-lock', 60));
        $this->assertFalse($lock->get('horizon:test-lock', 60));
    }

    #[Test]
    public function it_grants_a_lock_again_once_it_has_expired(): void
    {
        $lock = $this->app->make(Lock::class);

        $this->assertTrue($lock->get('horizon:test-lock', -1));
        $this->assertTrue($lock->get('horizon:test-lock', 60));
    }

    #[Test]
    public function it_runs_the_callback_when_the_lock_is_available(): void
    {
        $lock = $this->app->make(Lock::class);

        $ran = false;

        $lock->with('horizon:test-lock', function () use (&$ran) {
            $ran = true;
        });

        $this->assertTrue($ran);
        $this->assertFalse($lock->exists('horizon:test-lock'));
    }
}
