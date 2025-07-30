<?php

namespace Iambateman\Speedrun\Commands;

use Iambateman\Speedrun\Enums\FeaturePhase;
use Iambateman\Speedrun\Services\DirectoryManager;
use Iambateman\Speedrun\Services\FeatureStateManager;
use Illuminate\Console\Command;

class PlanCommand extends Command
{
    protected $signature = 'speedrun:feature-plan {name} {--transition}';
    protected $description = 'Manage the planning phase of a feature';

    public function __construct(
        private FeatureStateManager $stateManager,
        private DirectoryManager $directoryManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $featureName = $this->argument('name');
        $feature = $this->stateManager->findFeature($featureName);

        if (! $feature) {
            $this->error("Feature '{$featureName}' not found.");
            return Command::FAILURE;
        }

        // Handle phase transition
        if ($this->option('transition')) {
            if ($feature->phase === FeaturePhase::DESCRIPTION) {
                $requireConfirmation = config('speedrun.features.transitions.require_confirmation', true);
                
                if (!$requireConfirmation || $this->confirm('Ready to move from description to planning?', true)) {
                    $this->stateManager->transitionFeature($feature, FeaturePhase::PLANNING);
                    $this->info("✅ Transitioned to planning phase");
                } else {
                    return Command::SUCCESS;
                }
            }
        }

        if ($feature->phase !== FeaturePhase::PLANNING) {
            $this->warn("Feature is not in planning phase (current: {$feature->phase->value})");
            return Command::FAILURE;
        }

        $this->info("📋 Planning Phase for: {$feature->name}");
        $this->newLine();
        $this->line("Claude Code will now help you create planning documents for:");
        $this->line("- Architecture and design decisions");
        $this->line("- Database schema if needed");
        $this->line("- API endpoints and contracts");
        $this->line("- Component structure");
        $this->line("- Test strategy");
        $this->newLine();
        $this->line("Each plan will be saved as a markdown document in the planning/ directory.");
        $this->newLine();
        $this->line("🤖 Ready to begin planning phase...");

        return Command::SUCCESS;
    }
}