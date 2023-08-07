<?php

namespace Iambateman\Speedrun;

use Iambateman\Speedrun\Actions\CheckForBugs;
use Iambateman\Speedrun\Actions\GenerateFilamentForModels;
use Iambateman\Speedrun\Actions\RunTask;
use Iambateman\Speedrun\Actions\InstallFilament;
use Iambateman\Speedrun\Actions\MakeTask;
use Iambateman\Speedrun\Actions\MakeFactory;
use Iambateman\Speedrun\Actions\MakeManyToManyMigrations;
use Iambateman\Speedrun\Actions\MakeMigrationToCreateModel;
use Iambateman\Speedrun\Actions\MakeModel;
use Iambateman\Speedrun\Actions\MakeTestForModel;
use Iambateman\Speedrun\Actions\OODA;
use Iambateman\Speedrun\Actions\RunMigrations;
use Iambateman\Speedrun\Actions\RunTests;
use Iambateman\Speedrun\Commands\DemoCommand;
use Iambateman\Speedrun\Commands\IndicateDemoPresenceCommand;
use Iambateman\Speedrun\Commands\InstallComposerPackage;
use Iambateman\Speedrun\Commands\RunArtisanCommand;
use Iambateman\Speedrun\Commands\RunHelpCommand;
use Iambateman\Speedrun\Commands\RunQueryCommand;
use Iambateman\Speedrun\Commands\SpeedrunCommand;
use Iambateman\Speedrun\DTO\Tool;
use Iambateman\Speedrun\Helpers\ToolList;
use Illuminate\Support\Collection;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SpeedrunServiceProvider extends PackageServiceProvider {

    protected Collection $tools;


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
                MakeModel::class,
                MakeMigrationToCreateModel::class,
                MakeFactory::class,
                MakeTestForModel::class,
                RunTests::class,
                RunMigrations::class,
                RunTask::class,
                MakeManyToManyMigrations::class,
                MakeTask::class,
                CheckForBugs::class,
                InstallFilament::class,
                GenerateFilamentForModels::class
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

    public function packageRegistered(): void
    {
        parent::packageRegistered();

        $this->app->booting(function () {
            ToolList::initialize();
        });
    }



}
