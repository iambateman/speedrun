<?php

namespace Iambateman\Speedrun;

use Iambateman\Speedrun\Actions\OODA;
use Iambateman\Speedrun\Actions\Tasks\DefineViewsToMake;
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
use Iambateman\Speedrun\Commands\FeatureCommand;
use Iambateman\Speedrun\Commands\DiscoverCommand;
use Iambateman\Speedrun\Commands\DescribeCommand;
use Iambateman\Speedrun\Commands\PlanCommand;
use Iambateman\Speedrun\Commands\ExecuteCommand;
use Iambateman\Speedrun\Commands\CleanupCommand;
use Iambateman\Speedrun\Commands\ImproveCommand;
use Iambateman\Speedrun\Commands\InstallCommand;
use Iambateman\Speedrun\Commands\CodebaseDescribeCommand;
use Iambateman\Speedrun\Helpers\ToolList;
use Iambateman\Speedrun\Services\DirectoryManager;
use Iambateman\Speedrun\Services\FeatureFileParser;
use Iambateman\Speedrun\Services\FeatureStateManager;
use Iambateman\Speedrun\Services\PlanningDocumentConverter;
use Iambateman\Speedrun\Services\CodebaseAnalyzer;
use Iambateman\Speedrun\Services\RouteAnalyzer;
use Iambateman\Speedrun\Services\ControllerAnalyzer;
use Iambateman\Speedrun\Services\ModelAnalyzer;
use Iambateman\Speedrun\Services\TestAnalyzer;
use Iambateman\Speedrun\Services\ViewAnalyzer;
use Iambateman\Speedrun\Services\FeatureGenerator;
use Illuminate\Support\Collection;
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
                InstallCommand::class,
                SpeedrunCommand::class,
                RunHelpCommand::class,
                DemoCommand::class,
                IndicateDemoPresenceCommand::class,
                FeatureCommand::class,
                DiscoverCommand::class,
                DescribeCommand::class,
                PlanCommand::class,
                ExecuteCommand::class,
                CleanupCommand::class,
                ImproveCommand::class,
                CodebaseDescribeCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();

        // Register feature management services as singletons
        $this->app->singleton(FeatureFileParser::class);
        $this->app->singleton(DirectoryManager::class);
        $this->app->singleton(FeatureStateManager::class);
        $this->app->singleton(PlanningDocumentConverter::class);

        // Register codebase analysis services as singletons
        $this->app->singleton(RouteAnalyzer::class);
        $this->app->singleton(ControllerAnalyzer::class);
        $this->app->singleton(ModelAnalyzer::class);
        $this->app->singleton(TestAnalyzer::class);
        $this->app->singleton(ViewAnalyzer::class);
        $this->app->singleton(FeatureGenerator::class);
        $this->app->singleton(CodebaseAnalyzer::class);
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        // Show install message if not installed
        if (!config('speedrun.installed', false) && $this->app->runningInConsole()) {
            $this->showInstallMessage();
        }

        // Publish Claude command files to .claude/commands
        $this->publishes([
            __DIR__.'/../resources/claude-commands' => base_path('.claude/commands'),
        ], 'speedrun-claude');

        // Publish config with specific tag
        $this->publishes([
            __DIR__.'/../config/speedrun.php' => config_path('speedrun.php'),
        ], 'speedrun-config');
    }

    protected function showInstallMessage(): void
    {
        if (!$this->app->runningUnitTests()) {
            echo "\n";
            echo "🚀 \033[1;36mSpeedrun Feature Management Package\033[0m\n";
            echo "\033[1;33mPlease run: php artisan speedrun:install\033[0m\n";
            echo "\n";
        }
    }
}
