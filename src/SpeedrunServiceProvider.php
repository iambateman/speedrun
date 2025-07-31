<?php

namespace Iambateman\Speedrun;

use Iambateman\Speedrun\Commands\CleanupCommand;
use Iambateman\Speedrun\Commands\DemoCommand;
use Iambateman\Speedrun\Commands\DescribeCommand;
use Iambateman\Speedrun\Commands\DiscoverCommand;
use Iambateman\Speedrun\Commands\ExecuteCommand;
use Iambateman\Speedrun\Commands\FeatureInitCommand;
use Iambateman\Speedrun\Commands\IndicateDemoPresenceCommand;
use Iambateman\Speedrun\Commands\InstallCommand;
use Iambateman\Speedrun\Commands\SpeedrunCommand;
use Illuminate\Support\Collection;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SpeedrunServiceProvider extends PackageServiceProvider
{
    protected Collection $tools;

    public function configurePackage(Package $package): void
    {
        // Only register in non-production environments
        if (app()->environment('production')) {
            return;
        }

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
                FeatureInitCommand::class,
                CleanupCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        // Only register in non-production environments
        if (app()->environment('production')) {
            return;
        }

        parent::packageRegistered();
    }

    public function packageBooted(): void
    {
        // Only boot in non-production environments
        if (app()->environment('production')) {
            return;
        }

        parent::packageBooted();

        // Show install message if not installed
        if (! config('speedrun.installed', false) && $this->app->runningInConsole()) {
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
        if (! $this->app->runningUnitTests()) {
            echo "\n";
            echo "🚀 \033[1;36mSpeedrun Feature Management Package\033[0m\n";
            echo "\033[1;33mPlease run: php artisan speedrun:install\033[0m\n";
            echo "\n";
        }
    }
}
