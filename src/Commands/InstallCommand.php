<?php

namespace Iambateman\Speedrun\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use function Laravel\Prompts\text;
use function Laravel\Prompts\confirm;

class InstallCommand extends Command
{
    protected $signature = 'speedrun:install {--force}';
    protected $description = 'Install and configure Speedrun package';

    public function handle(): int
    {
        $this->info('🚀 Installing Speedrun Feature Management Package');
        $this->newLine();

        // Check if already installed
        if (config('speedrun.installed', false) && !$this->option('force')) {
            $this->warn('Speedrun is already installed. Use --force to reinstall.');
            return Command::SUCCESS;
        }

        // Get directory preferences
        $this->info('📁 Configure your feature directories:');
        
        $defaultBase = '_docs';
        $baseDir = text(
            label: 'Base directory for features',
            default: $defaultBase,
            hint: 'This will contain your wip/, features/, and archive/ subdirectories'
        );

        $wipDir = $baseDir . '/wip';
        $featuresDir = $baseDir . '/features';
        $archiveDir = $baseDir . '/archive';

        $this->line("  📝 Work-in-progress: {$wipDir}");
        $this->line("  ✅ Completed features: {$featuresDir}");
        $this->line("  📦 Archived features: {$archiveDir}");
        $this->newLine();

        if (!confirm('Create these directories and install Speedrun?', true)) {
            $this->info('Installation cancelled.');
            return Command::SUCCESS;
        }

        // Create directories
        $this->info('Creating directories...');
        $directories = [$wipDir, $featuresDir, $archiveDir];
        
        foreach ($directories as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
                $this->line("  ✅ Created: {$dir}");
            } else {
                $this->line("  📁 Exists: {$dir}");
            }
        }

        // Publish configuration
        $this->info('Publishing configuration...');
        $this->call('vendor:publish', [
            '--provider' => 'Iambateman\Speedrun\SpeedrunServiceProvider',
            '--tag' => 'speedrun-config',
            '--force' => $this->option('force')
        ]);

        // Update configuration with user preferences
        $configPath = config_path('speedrun.php');
        if (File::exists($configPath)) {
            $config = File::get($configPath);
            
            // Update the directories configuration
            $config = preg_replace(
                "/('wip' => env\('SPEEDRUN_WIP_DIR', ')[^']*('\),)/",
                "$1{$wipDir}$2",
                $config
            );
            
            $config = preg_replace(
                "/('completed' => env\('SPEEDRUN_COMPLETED_DIR', ')[^']*('\),)/",
                "$1{$featuresDir}$2",
                $config
            );
            
            $config = preg_replace(
                "/('archive' => env\('SPEEDRUN_ARCHIVE_DIR', ')[^']*('\),)/",
                "$1{$archiveDir}$2",
                $config
            );

            // Mark as installed
            $config = str_replace(
                "'installed' => false,",
                "'installed' => true,",
                $config
            );

            File::put($configPath, $config);
            $this->line('  ✅ Updated configuration');
        }

        // Publish Claude commands
        $this->info('Publishing Claude Code commands...');
        $this->call('vendor:publish', [
            '--provider' => 'Iambateman\Speedrun\SpeedrunServiceProvider',
            '--tag' => 'speedrun-claude',
            '--force' => $this->option('force')
        ]);

        $this->newLine();
        $this->info('🎉 Speedrun installation complete!');
        $this->newLine();
        $this->line('📖 Quick start:');
        $this->line('  • Use /feature in Claude Code to create or manage features');
        $this->line('  • Use /improve to enhance completed features');
        $this->line('  • Run "php artisan speedrun:feature" to get started');
        $this->newLine();
        $this->warn('⚠️  If you\'re currently running Claude Code, you may need to restart it');
        $this->line('   to recognize the newly installed commands.');
        $this->newLine();
        $this->line('📁 Your features will be stored in:');
        foreach ($directories as $dir) {
            $this->line("  • {$dir}");
        }

        return Command::SUCCESS;
    }
}