<?php

namespace Iambateman\Speedrun\Services;

use Carbon\Carbon;
use Iambateman\Speedrun\Enums\FeaturePhase;
use Iambateman\Speedrun\Exceptions\FeatureLockedException;
use Iambateman\Speedrun\Exceptions\InvalidPhaseException;
use Iambateman\Speedrun\Models\Feature;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class FeatureStateManager
{
    public function __construct(
        private FeatureFileParser $parser,
        private DirectoryManager $directoryManager
    ) {}

    public function createFeature(
        string $name,
        ?string $parentFeature = null,
        ?string $parentRelationship = null
    ): Feature {
        $path = $this->directoryManager->createFeatureDirectory($name);
        $this->directoryManager->createFeatureSubdirectories($path);
        
        $feature = $this->parser->createNewFeature($name, $path, $parentFeature, $parentRelationship);
        $this->acquireLock($feature);
        
        return $feature;
    }

    public function findFeature(string $name): ?Feature
    {
        // Check WIP directory first
        $wipPath = $this->directoryManager->getWipDirectory() . '/' . $name . '/_' . $name . '.md';
        if (File::exists($wipPath)) {
            try {
                return $this->parser->parse($wipPath);
            } catch (\Exception $e) {
                // Skip corrupted features
                return null;
            }
        }

        // Check completed directory
        $completedPath = $this->directoryManager->getCompletedDirectory() . '/' . $name . '/_' . $name . '.md';
        if (File::exists($completedPath)) {
            try {
                return $this->parser->parse($completedPath);
            } catch (\Exception $e) {
                // Skip corrupted features
                return null;
            }
        }

        return null;
    }

    public function loadFeature(string $name): ?Feature
    {
        return $this->findFeature($name);
    }

    public function loadCompletedFeature(string $name): ?Feature
    {
        $completedPath = $this->directoryManager->getCompletedDirectory() . '/' . $name . '/_' . $name . '.md';
        if (File::exists($completedPath)) {
            try {
                return $this->parser->parse($completedPath);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    public function getAllFeatures(): Collection
    {
        $features = collect();
        
        // Get WIP features
        $wipDir = $this->directoryManager->getWipDirectory();
        if (File::exists($wipDir)) {
            $wipFeatures = $this->getFeaturesFromDirectory($wipDir);
            $features = $features->merge($wipFeatures);
        }

        // Get completed features
        $completedDir = $this->directoryManager->getCompletedDirectory();
        if (File::exists($completedDir)) {
            $completedFeatures = $this->getFeaturesFromDirectory($completedDir);
            $features = $features->merge($completedFeatures);
        }

        return $features;
    }

    public function getCompletedFeatures(): Collection
    {
        $completedDir = $this->directoryManager->getCompletedDirectory();
        if (!File::exists($completedDir)) {
            return collect();
        }

        return $this->getFeaturesFromDirectory($completedDir);
    }

    public function featureExists(string $name): bool
    {
        return $this->findFeature($name) !== null;
    }

    public function transitionFeature(Feature $feature, FeaturePhase $newPhase): void
    {
        if (!$feature->phase->canTransitionTo($newPhase)) {
            throw new InvalidPhaseException($feature->phase, $newPhase);
        }

        $this->checkLock($feature);
        
        $feature->transitionTo($newPhase);
        $this->parser->save($feature);
    }

    public function acquireLock(Feature $feature): void
    {
        $lockFile = $feature->path . '/.lock';
        
        // Check if already locked by another process
        if (File::exists($lockFile)) {
            $lockData = json_decode(File::get($lockFile), true);
            
            // Check if lock is stale
            $lockedAt = Carbon::parse($lockData['locked_at']);
            $timeoutMinutes = config('speedrun.features.locking.timeout_minutes', 60);
            
            if ($lockedAt->addMinutes($timeoutMinutes)->isFuture() && 
                $lockData['pid'] !== getmypid()) {
                throw new FeatureLockedException($feature->name, $lockData['locked_by']);
            }
        }

        // Create new lock
        $lockData = [
            'locked_at' => now()->toIso8601String(),
            'locked_by' => get_current_user() ?: 'unknown',
            'pid' => getmypid(),
        ];

        File::put($lockFile, json_encode($lockData, JSON_PRETTY_PRINT));
    }

    public function releaseLock(Feature $feature): void
    {
        $lockFile = $feature->path . '/.lock';
        if (File::exists($lockFile)) {
            File::delete($lockFile);
        }
    }

    private function getFeaturesFromDirectory(string $directory): Collection
    {
        $features = collect();
        
        $directories = File::directories($directory);
        
        foreach ($directories as $featureDir) {
            $featureName = basename($featureDir);
            $featureFile = $featureDir . '/_' . $featureName . '.md';
            
            if (File::exists($featureFile)) {
                try {
                    $feature = $this->parser->parse($featureFile);
                    $features->push($feature);
                } catch (\Exception $e) {
                    // Skip corrupted features
                    continue;
                }
            }
        }
        
        return $features;
    }

    private function checkLock(Feature $feature): void
    {
        $lockFile = $feature->path . '/.lock';
        
        if (!File::exists($lockFile)) {
            $this->acquireLock($feature);
            return;
        }

        $lockData = json_decode(File::get($lockFile), true);
        
        // Check if locked by current process
        if ($lockData['pid'] === getmypid()) {
            return;
        }

        // Check if lock is stale
        $lockedAt = Carbon::parse($lockData['locked_at']);
        $timeoutMinutes = config('speedrun.features.locking.timeout_minutes', 60);
        
        if ($lockedAt->addMinutes($timeoutMinutes)->isPast()) {
            // Clear stale lock and acquire new one
            File::delete($lockFile);
            $this->acquireLock($feature);
            return;
        }

        throw new FeatureLockedException($feature->name, $lockData['locked_by']);
    }
}