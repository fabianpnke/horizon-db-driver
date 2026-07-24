<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Listeners;

use Carbon\CarbonImmutable;
use HorizonDbDriver\HorizonDbDriver\Repositories\DatabaseTagRepository;
use Laravel\Horizon\Events\MasterSupervisorLooped;

class TrimTags
{
    /**
     * The last time the tags were trimmed.
     */
    public ?CarbonImmutable $lastTrimmed = null;

    /**
     * How many minutes to wait in between each trim.
     */
    public int $frequency = 5;

    /**
     * Create the event listener.
     */
    public function __construct(protected DatabaseTagRepository $tags) {}

    /**
     * Handle the event.
     */
    public function handle(MasterSupervisorLooped $event): void
    {
        if ($this->lastTrimmed === null) {
            $this->lastTrimmed = CarbonImmutable::now()->subMinutes($this->frequency + 1);
        }

        if ($this->lastTrimmed->lte(CarbonImmutable::now()->subMinutes($this->frequency))) {
            $this->tags->trimExpired();

            $this->lastTrimmed = CarbonImmutable::now();
        }
    }
}
