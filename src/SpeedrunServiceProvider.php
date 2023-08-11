<?php

namespace Iambateman\Speedrun;

use Iambateman\Speedrun\Actions\OODA;
use Iambateman\Speedrun\Actions\Tasks\MakeTask;
use Iambateman\Speedrun\Actions\Tasks\PruneIncompleteTasks;
use Iambateman\Speedrun\Actions\Tasks\RunTask;
use Iambateman\Speedrun\Actions\Tasks\UndoTask;
use Iambateman\Speedrun\Actions\Tools\CheckForBugs;
use Iambateman\Speedrun\Actions\Tools\GenerateFilamentForModels;
use Iambateman\Speedrun\Actions\Tools\GetBladeComponents;
use Iambateman\Speedrun\Actions\Tools\GetModels;
use Iambateman\Speedrun\Actions\Tools\InstallFilament;
use Iambateman\Speedrun\Actions\Tools\MakeFactory;
use Iambateman\Speedrun\Actions\Tools\MakeLivewirePage;
use Iambateman\Speedrun\Actions\Tools\MakeManyToManyMigrations;
use Iambateman\Speedrun\Actions\Tools\MakeMigrationToCreateModel;
use Iambateman\Speedrun\Actions\Tools\MakeModel;
use Iambateman\Speedrun\Actions\Tools\MakeTestForModel;
use Iambateman\Speedrun\Actions\Utilities\RunMigrations;
use Iambateman\Speedrun\Actions\Utilities\RunTests;
use Iambateman\Speedrun\Commands\DemoCommand;
use Iambateman\Speedrun\Commands\IndicateDemoPresenceCommand;
use Iambateman\Speedrun\Commands\InstallComposerPackage;
use Iambateman\Speedrun\Commands\RunArtisanCommand;
use Iambateman\Speedrun\Commands\RunHelpCommand;
use Iambateman\Speedrun\Commands\RunQueryCommand;
use Iambateman\Speedrun\Commands\SpeedrunCommand;
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
                GenerateFilamentForModels::class,
                PruneIncompleteTasks::class,
                UndoTask::class,
                GetBladeComponents::class,
                GetModels::class,
                MakeLivewirePage::class,
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
