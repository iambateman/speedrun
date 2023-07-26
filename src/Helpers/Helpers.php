<?php

namespace Iambateman\Speedrun\Helpers;

use Iambateman\Speedrun\Exceptions\ProductionException;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class Helpers
{
    public static function dieInProduction()
    {
        if (Speedrun::dieInProduction() && app()->isProduction()) {
            throw new ProductionException('You are in production and the config is set to stop production commands.');
        }
    }

    public static function getModels(): Collection
    {
        $models = collect(File::allFiles(app_path()))
            ->map(function ($item) {
                $path = $item->getRelativePathName();
                $class = sprintf('\%s%s',
                    Container::getInstance()->getNamespace(),
                    strtr(substr($path, 0, strrpos($path, '.')), '/', '\\'));

                return $class;
            })
            ->filter(function ($class) {
                $valid = false;

                if (class_exists($class)) {
                    $reflection = new \ReflectionClass($class);
                    $valid = $reflection->isSubclassOf(Model::class) &&
                        ! $reflection->isAbstract();
                }

                return $valid;
            });

        return $models->values();
    }

    /**
     * @param $obj
     *
     * Comes from Livewire's invade() function.
     */
    public static function invade($obj)
    {
        return new class($obj)
        {
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
}
