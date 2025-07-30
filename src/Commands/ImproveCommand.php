<?php

namespace Iambateman\Speedrun\Commands;

use Iambateman\Speedrun\Enums\FeaturePhase;
use Iambateman\Speedrun\Services\DirectoryManager;
use Iambateman\Speedrun\Services\FeatureStateManager;
use Iambateman\Speedrun\Services\FeatureFileParser;
use Illuminate\Console\Command;

class ImproveCommand extends Command
{
    protected $signature = 'speedrun:feature-improve {name?}';
    protected $description = 'Improve an existing completed feature';

    public function __construct(
        private FeatureStateManager $stateManager,
        private DirectoryManager $directoryManager,
        private FeatureFileParser $parser
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $featureName = $this->argument('name');

        if (! $featureName) {
            // Search for completed features
            $features = $this->stateManager->getCompletedFeatures();
            
            if ($features->isEmpty()) {
                $this->warn('No completed features found');
                return Command::FAILURE;
            }

            $this->info('Search for a feature to improve');
            $this->info('Completed features available for improvement:');
            foreach ($features as $feature) {
                $this->line("- {$feature->name}");
            }

            // Check if we should prompt for input (only when no feature name provided)
            $requirePrompts = config('speedrun.features.prompts.require_input', true);
            
            if (!$requirePrompts) {
                return Command::SUCCESS;
            }

            $featureName = $this->ask('Enter the name of the feature to improve');

            if (!$featureName) {
                $this->info('No feature selected.');
                return Command::SUCCESS;
            }
        }

        $feature = $this->stateManager->loadCompletedFeature($featureName);
        
        if (! $feature) {
            $this->error("Completed feature '{$featureName}' not found.");
            return Command::FAILURE;
        }

        $improvement = $this->ask('What would you like to improve about this feature?', 'General improvements and enhancements');

        // Validate improvement reason - retry if empty
        while (!$improvement || trim($improvement) === '') {
            $improvement = $this->ask('What would you like to improve about this feature?');
        }

        // Move back to WIP
        $this->directoryManager->moveToWip($feature);
        $this->stateManager->transitionFeature($feature, FeaturePhase::DESCRIPTION);

        // Add improvement note to feature
        $feature->addImprovementNote($improvement);
        
        // Save the updated feature
        $this->parser->save($feature);

        $this->info("Feature moved back to work-in-progress");
        $this->info("Improvement goal: {$improvement}");
        $this->newLine();
        $this->line("🤖 Claude Code will now help you improve this feature...");
        $this->line("The feature has been reset to the description phase where you can");
        $this->line("refine requirements and plan the improvements.");

        return Command::SUCCESS;
    }
}