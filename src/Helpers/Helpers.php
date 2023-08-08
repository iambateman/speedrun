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
            throw new ProductionException('You are in production and the config is set to stop production commands.');
        }
    }


    /**
     * @param $obj
     *
     * Comes from Livewire's invade() function.
     */
    public static function invade($obj)
    {
        return new class($obj) {

            public $obj;

            public $reflected;

            public function __construct($obj)
            {
                $this->obj = $obj;
                $this->reflected = new ReflectionClass($obj);
            }

            public function __get($name)
            {
                $property = $this->reflected->getProperty($name);

                $property->setAccessible(true);

                return $property->getValue($this->obj);
            }

            public function __set($name, $value)
            {
                $property = $this->reflected->getProperty($name);

                $property->setAccessible(true);

                $property->setValue($this->obj, $value);
            }

            public function __call($name, $params)
            {
                $method = $this->reflected->getMethod($name);

                $method->setAccessible(true);

                return $method->invoke($this->obj, ...$params);
            }

        };
    }


    public static function handleModelName($modelName, bool $uppercase = true)
    {
        if ($uppercase) {
            return str($modelName)->singular()->title();
        }

        return str($modelName)->singular()->lower();
    }


    public static function getModelPath($modelName, bool $mustBeNew = false): string
    {
        $modelName = str($modelName)->title()->toString();
        $path = app_path("Models/{$modelName}.php");

        if (File::exists($path) && $mustBeNew) {
            return '';
        }

        return $path;
    }


    public static function command_exists($name)
    {
        return Arr::has(Artisan::all(), $name);
    }


}
