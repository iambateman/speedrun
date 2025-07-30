<?php

namespace Iambateman\Speedrun\Commands;

use Iambateman\Speedrun\Services\DirectoryManager;
use Iambateman\Speedrun\Services\FeatureStateManager;
use Illuminate\Console\Command;

class DiscoverCommand extends Command
{
    protected $signature = 'speedrun:feature-discover {--create=}';
    protected $description = 'Discover existing features or create a new one';

    public function __construct(
        private FeatureStateManager $stateManager,
        private DirectoryManager $directoryManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->directoryManager->ensureDirectoriesExist();

        if ($createName = $this->option('create')) {
            return $this->createNewFeature($createName);
        }

        // Get all existing features
        $features = $this->stateManager->getAllFeatures();

        if ($features->isEmpty()) {
            $this->info('No existing features found.');
            return $this->createNewFeature();
        }

        // For now, just list the features - later we can add search functionality
        $this->info('Existing features:');
        foreach ($features as $feature) {
            $this->line("- {$feature->name} ({$feature->phase->value})");
        }

        $featureName = $this->ask('Enter feature name to resume, or new name to create');
        
        if ($this->stateManager->featureExists($featureName)) {
            return $this->call('speedrun:feature', ['name' => $featureName]);
        }

        return $this->createNewFeature($featureName);
    }

    private function createNewFeature(?string $name = null): int
    {
        $name = $name ?? $this->ask('Enter the feature name (kebab-case)', null, function ($value) {
            if (! preg_match('/^[a-z0-9\-]+$/', $value)) {
                throw new \InvalidArgumentException('Feature name must be kebab-case (lowercase letters, numbers, and hyphens only)');
            }
            if ($this->stateManager->featureExists($value)) {
                throw new \InvalidArgumentException('A feature with this name already exists');
            }
            return $value;
        });

        // For now, skip parent feature selection - can be added later
        $parentFeature = null;
        $parentRelationship = null;

        // Create the feature
        $feature = $this->stateManager->createFeature(
            name: $name,
            parentFeature: $parentFeature,
            parentRelationship: $parentRelationship
        );

        $this->info("✅ Created new feature: {$name}");
        $this->line("📁 Location: {$feature->path}");

        // Move to description phase
        return $this->call('speedrun:feature-describe', ['name' => $name]);
    }
}