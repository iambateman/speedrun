<?php

namespace Iambateman\Speedrun;

use Iambateman\Speedrun\Commands\InstallComposerPackage;
use Iambateman\Speedrun\Commands\RunArtisanCommand;
use Iambateman\Speedrun\Commands\RunHelpCommand;
use Iambateman\Speedrun\Commands\RunQueryCommand;
use Iambateman\Speedrun\Commands\SpeedrunCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SpeedrunServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('speedrun')
            ->hasConfigFile()
            ->hasCommands([
                InstallComposerPackage::class,
                SpeedrunCommand::class,
                RunArtisanCommand::class,
                RunHelpCommand::class,
                RunQueryCommand::class,
            ]);
    }
}
