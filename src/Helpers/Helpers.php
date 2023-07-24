<?php

namespace Iambateman\Speedrun\Helpers;

use Iambateman\Speedrun\Exceptions\ProductionException;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

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
}
