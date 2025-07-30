<?php

use Iambateman\Speedrun\Services\FeatureStateManager;
use Iambateman\Speedrun\Enums\FeaturePhase;
use Iambateman\Speedrun\Exceptions\InvalidPhaseException;
use Iambateman\Speedrun\Exceptions\FeatureLockedException;

beforeEach(function () {
    $this->stateManager = app(FeatureStateManager::class);
});

describe('FeatureStateManager', function () {
    
    it('creates a new feature with correct initial state', function () {
        $feature = $this->stateManager->createFeature('test-feature');
        
        expect($feature->name)->toBe('test-feature');
        expect($feature->phase)->toBe(FeaturePhase::DESCRIPTION);
        expect($feature->path)->toContain('/wip/test-feature');
        expect($feature->createdAt)->toBeInstanceOf(Carbon\Carbon::class);
        expect($feature->lastUpdated)->toBeInstanceOf(Carbon\Carbon::class);
    });

    it('creates feature directory structure', function () {
        $feature = $this->stateManager->createFeature('structure-test');
        
        expect($feature->path)->toHaveFeatureStructure();
    });

    it('validates phase transitions correctly', function () {
        $feature = $this->stateManager->createFeature('transition-test');
        
        // Valid transition: description -> planning
        $this->stateManager->transitionFeature($feature, FeaturePhase::PLANNING);
        expect($feature->phase)->toBe(FeaturePhase::PLANNING);
        
        // Invalid transition: planning -> complete (skipping execution)
        expect(fn () => $this->stateManager->transitionFeature($feature, FeaturePhase::COMPLETE))
            ->toThrow(InvalidPhaseException::class);
    });

    it('finds features in both wip and completed directories', function () {
        // Create WIP feature
        $this->createFeatureFixture('wip-feature');
        
        // Create completed feature
        $this->createCompletedFeatureFixture('completed-feature');
        
        expect($this->stateManager->findFeature('wip-feature'))->not->toBeNull();
        expect($this->stateManager->findFeature('completed-feature'))->not->toBeNull();
        expect($this->stateManager->findFeature('nonexistent-feature'))->toBeNull();
    });

    it('handles feature locking correctly', function () {
        $feature = $this->stateManager->createFeature('locked-feature');
        
        // Feature should be locked after creation
        $lockFile = $feature->path . '/.lock';
        expect(file_exists($lockFile))->toBeTrue();
        
        // Lock data should be valid
        $lockData = json_decode(file_get_contents($lockFile), true);
        expect($lockData)->toHaveKey('locked_at');
        expect($lockData)->toHaveKey('locked_by');
        expect($lockData)->toHaveKey('pid');
        
        // Release lock
        $this->stateManager->releaseLock($feature);
        expect(file_exists($lockFile))->toBeFalse();
    });

    it('prevents operations on locked features', function () {
        $feature = $this->stateManager->createFeature('concurrent-test');
        
        // Simulate different process lock
        $lockData = [
            'locked_at' => now()->toIso8601String(),
            'locked_by' => 'other_user',
            'pid' => 99999, // Different PID
        ];
        file_put_contents($feature->path . '/.lock', json_encode($lockData));
        
        expect(fn () => $this->stateManager->transitionFeature($feature, FeaturePhase::PLANNING))
            ->toThrow(FeatureLockedException::class);
    });

    it('clears stale locks automatically', function () {
        $feature = $this->stateManager->createFeature('stale-lock-test');
        
        // Create stale lock (old timestamp)
        $lockData = [
            'locked_at' => now()->subHours(2)->toIso8601String(),
            'locked_by' => 'old_user',
            'pid' => 99999,
        ];
        file_put_contents($feature->path . '/.lock', json_encode($lockData));
        
        // Should clear stale lock and allow operation
        $this->stateManager->transitionFeature($feature, FeaturePhase::PLANNING);
        expect($feature->phase)->toBe(FeaturePhase::PLANNING);
    });

    it('loads existing features correctly', function () {
        $originalPath = $this->createFeatureFixture('load-test', [
            'frontmatter' => [
                'phase' => 'planning',
                'parent_feature' => '../parent/_parent.md',
                'test_paths' => ['tests/Feature/LoadTest.php'],
            ]
        ]);
        
        $feature = $this->stateManager->findFeature('load-test');
        
        expect($feature->name)->toBe('load-test');
        expect($feature->phase)->toBe(FeaturePhase::PLANNING);
        expect($feature->parentFeature)->toBe('../parent/_parent.md');
        expect($feature->testPaths)->toContain('tests/Feature/LoadTest.php');
    });

    it('returns all features from both directories', function () {
        $this->createFeatureFixture('wip-1');
        $this->createFeatureFixture('wip-2');
        $this->createCompletedFeatureFixture('completed-1');
        $this->createCompletedFeatureFixture('completed-2');
        
        $features = $this->stateManager->getAllFeatures();
        
        expect($features)->toHaveCount(4);
        expect($features->pluck('name')->toArray())->toContain('wip-1', 'wip-2', 'completed-1', 'completed-2');
    });

    it('returns only completed features', function () {
        $this->createFeatureFixture('wip-feature');
        $this->createCompletedFeatureFixture('completed-feature-1');
        $this->createCompletedFeatureFixture('completed-feature-2');
        
        $completedFeatures = $this->stateManager->getCompletedFeatures();
        
        expect($completedFeatures)->toHaveCount(2);
        expect($completedFeatures->pluck('name')->toArray())->toContain('completed-feature-1', 'completed-feature-2');
        expect($completedFeatures->pluck('name')->toArray())->not->toContain('wip-feature');
    });

    it('checks feature existence correctly', function () {
        $this->createFeatureFixture('existing-feature');
        
        expect($this->stateManager->featureExists('existing-feature'))->toBeTrue();
        expect($this->stateManager->featureExists('nonexistent-feature'))->toBeFalse();
    });

    it('updates feature metadata on transitions', function () {
        $feature = $this->stateManager->createFeature('metadata-test');
        $originalTimestamp = $feature->lastUpdated;
        
        // Small delay to ensure timestamp difference
        usleep(10000);
        
        $this->stateManager->transitionFeature($feature, FeaturePhase::PLANNING);
        
        expect($feature->lastUpdated->isAfter($originalTimestamp))->toBeTrue();
    });

    it('persists feature changes to disk', function () {
        $feature = $this->stateManager->createFeature('persistence-test');
        $this->stateManager->transitionFeature($feature, FeaturePhase::PLANNING);
        
        // Reload feature from disk
        $reloadedFeature = $this->stateManager->findFeature('persistence-test');
        
        expect($reloadedFeature->phase)->toBe(FeaturePhase::PLANNING);
    });

    it('handles parent feature relationships', function () {
        $feature = $this->stateManager->createFeature(
            name: 'child-feature',
            parentFeature: '../parent-feature/_parent-feature.md',
            parentRelationship: 'Extends parent with additional functionality'
        );
        
        expect($feature->parentFeature)->toBe('../parent-feature/_parent-feature.md');
        expect($feature->parentRelationship)->toBe('Extends parent with additional functionality');
    });

    it('gracefully handles corrupted feature files', function () {
        $this->createCorruptedFeature('corrupted-test');
        
        // Should return null for corrupted features instead of throwing
        $features = $this->stateManager->getAllFeatures();
        expect($features->pluck('name')->toArray())->not->toContain('corrupted-test');
    });

});