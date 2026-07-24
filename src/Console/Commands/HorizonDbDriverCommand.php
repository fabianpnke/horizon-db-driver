<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Console\Commands;

use Illuminate\Console\Command;

class HorizonDbDriverCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'horizon-db-driver:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package horizon-db-driver.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('HorizonDbDriver placeholder command executed.');

        return self::SUCCESS;
    }
}
