<?php

namespace Iambateman\Speedrun\Helpers;

use Iambateman\Speedrun\Exceptions\ProductionException;
use Iambateman\Speedrun\Speedrun;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class Helpers {

    public static function dieInProduction()
    {
        if (Speedrun::dieInProduction() && app()->isProduction()) {
            throw new \Exception('You are in production and the config is set to stop production commands.');
        }
    }

    public static function command_exists($name)
    {
        return Arr::has(Artisan::all(), $name);
    }


}
