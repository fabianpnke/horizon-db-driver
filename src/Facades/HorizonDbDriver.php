<?php

declare(strict_types=1);

namespace HorizonDbDriver\HorizonDbDriver\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \HorizonDbDriver\HorizonDbDriver\HorizonDbDriver
 */
class HorizonDbDriver extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \HorizonDbDriver\HorizonDbDriver\HorizonDbDriver::class;
    }
}
