<?php

namespace Iambateman\Speedrun\Commands;

use Iambateman\Speedrun\Enums\FeaturePhase;
use Iambateman\Speedrun\Services\DirectoryManager;
use Iambateman\Speedrun\Services\FeatureStateManager;
use Iambateman\Speedrun\Services\FeatureFileParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupCommand extends Command
{
    protected $signature = 'speedrun:feature-cleanup {name}';
    protected $description = 'Clean up planning artifacts after feature completion';

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
        $feature = $this->stateManager->findFeature($featureName);

        if (! $feature) {
            $this->error("Feature '{$featureName}' not found.");
            return Command::FAILURE;
        }

        $this->stateManager->transitionFeature($feature, FeaturePhase::CLEANUP);

        // Get all artifacts
        $planningDocs = $feature->getPlanningDocuments();
        $researchFiles = $feature->getResearchFiles();
        
        $this->info("🧹 Cleanup Phase for: {$feature->name}");
        $this->line("Found {$planningDocs->count()} planning documents");
        $this->line("Found {$researchFiles->count()} research files");

        if ($planningDocs->isEmpty() && $researchFiles->isEmpty()) {
            $this->info('No artifacts to clean up.');
            
            // Mark current improvement as complete if any
            $feature->markCurrentImprovementComplete();
            $this->parser->save($feature);
            
            $this->stateManager->transitionFeature($feature, FeaturePhase::COMPLETE);
            return Command::SUCCESS;
        }

        // Determine which files to keep
        $keepByDefault = config('speedrun.features.cleanup.planning_docs_to_keep', []);
        $requireConfirmation = config('speedrun.features.transitions.require_confirmation', true);
        
        $filesToDelete = [];
        $filesToKeep = [];

        // Handle planning documents
        foreach ($planningDocs as $doc) {
            if (in_array($doc->filename, $keepByDefault)) {
                $filesToKeep[] = $doc->filename;
            } else {
                $keep = $requireConfirmation ? $this->confirm("Keep planning document: {$doc->filename}?", false) : false;
                if ($keep) {
                    $filesToKeep[] = $doc->filename;
                } else {
                    $filesToDelete[] = $doc;
                }
            }
        }

        // Handle research files
        foreach ($researchFiles as $file) {
            $keep = $requireConfirmation ? $this->confirm("Keep research file: {$file->filename}?", false) : false;
            if (!$keep) {
                $filesToDelete[] = $file;
            } else {
                $filesToKeep[] = $file->filename;
            }
        }

        // Delete selected files
        foreach ($filesToDelete as $file) {
            if (File::exists($file->path)) {
                File::delete($file->path);
                $this->line("🗑️  Deleted: {$file->filename}");
            }
        }

        if (!empty($filesToKeep)) {
            $this->info("📁 Kept " . count($filesToKeep) . " files:");
            foreach ($filesToKeep as $filename) {
                $this->line("  ✅ {$filename}");
            }
        }

        // Mark current improvement as complete if any
        $feature->markCurrentImprovementComplete();
        $this->parser->save($feature);
        
        // Mark feature as complete
        $this->stateManager->transitionFeature($feature, FeaturePhase::COMPLETE);
        
        $this->newLine();
        $this->info("✅ Feature '{$featureName}' is now complete!");

        return Command::SUCCESS;
    }
}