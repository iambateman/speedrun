<?php

namespace Iambateman\Speedrun\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class InstallCommand extends Command
{
    protected $signature = 'speedrun:install {--force}';

    protected $description = 'Install and configure Speedrun package';

    public function handle(): int
    {
        $this->info('🚀 Installing Speedrun Feature Management Package');
        $this->newLine();

        // Check if already installed
        if (config('speedrun.installed', false) && ! $this->option('force')) {
            $this->warn('Speedrun is already installed. Use --force to reinstall.');

            return Command::SUCCESS;
        }

        // Get directory preferences
        $this->info('📁 Configure your feature directories:');

        $defaultBase = '_docs';
        $baseDir = text(
            label: 'Base directory for features',
            default: $defaultBase,
            hint: 'This will contain your wip/ and features/ subdirectories'
        );

        $wipDir = $baseDir.'/wip';
        $featuresDir = $baseDir.'/features';

        $this->line("  📝 Work-in-progress: {$wipDir}");
        $this->line("  ✅ Completed features: {$featuresDir}");
        $this->newLine();

        if (! confirm('Create these directories and install Speedrun?', true)) {
            $this->info('Installation cancelled.');

            return Command::SUCCESS;
        }

        // Create directories
        $this->info('Creating directories...');
        $directories = [$wipDir, $featuresDir];

        foreach ($directories as $dir) {
            if (! File::exists($dir)) {
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
            '--force' => $this->option('force'),
        ]);

        // Update configuration with user preferences
        $configPath = config_path('speedrun.php');
        if (File::exists($configPath)) {
            $config = File::get($configPath);

            // Update the base directory configuration
            $config = preg_replace(
                "/('directory' => env\('SPEEDRUN_DIR', ')[^']*('\),)/",
                "$1{$baseDir}$2",
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
            '--force' => $this->option('force'),
        ]);

        // Publish Claude agents
        $this->info('Publishing Claude Code agents...');
        $this->call('vendor:publish', [
            '--provider' => 'Iambateman\Speedrun\SpeedrunServiceProvider',
            '--tag' => 'speedrun-agents',
            '--force' => $this->option('force'),
        ]);

        // Update CLAUDE.md with Speedrun information
        $this->updateClaudeMd($baseDir);

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

    private function updateClaudeMd(string $baseDir): void
    {
        $this->info('Updating CLAUDE.md with Speedrun information...');
        
        $claudeMdPath = base_path('CLAUDE.md');
        $speedrunNote = $this->getSpeedrunClaudeNote($baseDir);
        
        if (File::exists($claudeMdPath)) {
            $content = File::get($claudeMdPath);
            
            // Check if Speedrun section already exists
            if (!str_contains($content, '## Speedrun Feature Management')) {
                // Append to existing CLAUDE.md
                $content .= "\n\n" . $speedrunNote;
                File::put($claudeMdPath, $content);
                $this->line('  ✅ Added Speedrun information to existing CLAUDE.md');
            } else {
                $this->line('  ℹ️  Speedrun information already exists in CLAUDE.md');
            }
        } else {
            // Create new CLAUDE.md
            File::put($claudeMdPath, $speedrunNote);
            $this->line('  ✅ Created CLAUDE.md with Speedrun information');
        }
    }

    private function getSpeedrunClaudeNote(string $baseDir): string
    {
        return "## Speedrun Feature Management

This project uses the Speedrun package for feature management and documentation.

### Feature Documentation Location
Feature documentation is stored in: `{$baseDir}/`
- `{$baseDir}/wip/` - Work-in-progress features
- `{$baseDir}/features/` - Completed features 

### AI Assistant Guidelines
For significant code efforts, you should:

1. **Check existing features first** - Before starting any major development work, search the feature directories above to see if relevant documentation already exists
2. **Look for context** - Feature files contain valuable context including:
   - Requirements and specifications
   - Technical decisions and rationale
   - Code file paths and relationships
   - Test coverage information
   - Implementation notes and gotchas

This helps maintain consistency and prevents duplicate work by leveraging existing feature knowledge.";
    }
}
