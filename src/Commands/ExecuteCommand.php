<?php

namespace Iambateman\Speedrun\Commands;

use Iambateman\Speedrun\Enums\FeaturePhase;
use Iambateman\Speedrun\Services\DirectoryManager;
use Iambateman\Speedrun\Services\FeatureStateManager;
use Iambateman\Speedrun\Services\PlanningDocumentConverter;
use Illuminate\Console\Command;

class ExecuteCommand extends Command
{
    protected $signature = 'speedrun:feature-execute {name} {--transition}';
    protected $description = 'Execute the feature by converting plans to code';

    public function __construct(
        private FeatureStateManager $stateManager,
        private DirectoryManager $directoryManager,
        private PlanningDocumentConverter $converter
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
            if ($feature->phase === FeaturePhase::PLANNING) {
                $requireConfirmation = config('speedrun.features.transitions.require_confirmation', true);
                
                if (!$requireConfirmation || $this->confirm('Ready to execute? This will convert planning documents to actual code.', true)) {
                    $this->stateManager->transitionFeature($feature, FeaturePhase::EXECUTION);
                    $this->info("✅ Transitioned to execution phase");
                } else {
                    return Command::SUCCESS;
                }
            }
        }

        if ($feature->phase !== FeaturePhase::EXECUTION) {
            $this->warn("Feature is not in execution phase (current: {$feature->phase->value})");
            return Command::FAILURE;
        }

        // Get all planning documents
        $planningDocs = $feature->getPlanningDocuments();
        
        if ($planningDocs->isEmpty()) {
            $this->warn('No planning documents found to execute.');
            $this->line('Create planning documents in the planning/ directory first.');
            return Command::FAILURE;
        }

        $this->info("Found {$planningDocs->count()} planning documents to convert");

        $convertedFiles = collect();

        // Convert each planning document
        foreach ($planningDocs as $doc) {
            $this->line("Converting: {$doc->filename}");
            
            try {
                $files = $this->converter->convert($doc);
                $convertedFiles = $convertedFiles->merge($files);
                $this->info("  ✅ Generated {$files->count()} files from {$doc->filename}");
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to convert {$doc->filename}: {$e->getMessage()}");
            }
        }

        // Update feature with generated files
        $feature->addCodePaths($convertedFiles->pluck('path')->toArray());
        
        // Move feature to completed directory (but keep in execution phase)
        $this->directoryManager->moveToCompleted($feature);

        $this->newLine();
        $this->info("✅ Execution complete! Generated {$convertedFiles->count()} files");
        $this->info("📁 Feature moved to: " . $feature->getCompletedPath());
        $this->line("Run 'speedrun:feature-cleanup {$featureName}' to clean up and mark as complete.");

        // Don't automatically run cleanup - let it be a separate step
        return Command::SUCCESS;
    }
}