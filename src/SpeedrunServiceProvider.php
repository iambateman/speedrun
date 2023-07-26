<?php

namespace Iambateman\Speedrun;

use Iambateman\Speedrun\Commands\DemoCommand;
use Iambateman\Speedrun\Commands\IndicateDemoPresenceCommand;
use Iambateman\Speedrun\Commands\InstallComposerPackage;
use Iambateman\Speedrun\Commands\RunArtisanCommand;
use Iambateman\Speedrun\Commands\RunHelpCommand;
use Iambateman\Speedrun\Commands\RunQueryCommand;
use Iambateman\Speedrun\Commands\SpeedrunCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
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
                DemoCommand::class,
                IndicateDemoPresenceCommand::class,
            ])->hasInstallCommand(function (InstallCommand $command) {
                $command->endWith(function (InstallCommand $command) {
                    $command->line('');
                    $command->info('DEMO');
                    $command->line('');
                    $command->line('To see how Speedrun works');
                    $command->line('with a quick demo, type:');
                    $command->line('php artisan speedrun:demo ', 'warn');
                });
            });
    }
}
