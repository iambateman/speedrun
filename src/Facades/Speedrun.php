<?php

namespace Iambateman\Speedrun\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Iambateman\Speedrun\Speedrun
 */
class Speedrun extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Iambateman\Speedrun\Speedrun::class;
    }
}
