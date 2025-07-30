<?php

use function Pest\Laravel\artisan;
use Illuminate\Support\Facades\File;

describe('Complete Feature Workflow', function () {

    it('completes full workflow from creation to cleanup', function () {
        // Step 1: Create new feature
        artisan('speedrun:feature workflow-complete')
            ->assertSuccessful();
        
        $this->assertFeatureExists('workflow-complete');
        $this->assertFeatureInPhase('workflow-complete', 'description');
        
        // Step 2: Transition to planning phase
        artisan('speedrun:feature-plan workflow-complete --transition')
            ->assertSuccessful();
        
        $this->assertFeatureInPhase('workflow-complete', 'planning');
        
        // Step 3: Create planning documents
        $featurePath = $this->testBasePath . '/wip/workflow-complete';
        $this->createPlanningDocument($featurePath, 'controller', 'app/Http/Controllers/WorkflowController.php');
        $this->createPlanningDocument($featurePath, 'model', 'app/Models/WorkflowComplete.php');
        
        // Step 4: Execute planning documents  
        artisan('speedrun:feature-execute workflow-complete --transition')
            ->assertSuccessful();
        
        // Feature should now be in completed directory
        $this->assertFeatureExists('workflow-complete', 'features');
        $this->assertFeatureInPhase('workflow-complete', 'execution', 'features');
        
        // Step 5: Cleanup phase
        artisan('speedrun:feature-cleanup workflow-complete')
            ->assertSuccessful();
        
        $this->assertFeatureInPhase('workflow-complete', 'complete', 'features');
    });

    it('persists data through all phases', function () {
        // Create feature with specific data
        $path = $this->createFeatureFixture('persistence-test', [
            'frontmatter' => [
                'parent_feature' => '../parent/_parent.md',
                'parent_relationship' => 'Extends parent with testing',
            ]
        ]);
        
        // Add some artifacts
        File::put($path . '/research/notes.md', 'Research notes');
        
        // Progress through phases
        artisan('speedrun:feature-plan persistence-test --transition')->assertSuccessful();
        artisan('speedrun:feature-execute persistence-test --transition')->assertSuccessful();
        artisan('speedrun:feature-cleanup persistence-test')->assertSuccessful();
        
        // Verify data persisted
        $stateManager = app(\Iambateman\Speedrun\Services\FeatureStateManager::class);
        $feature = $stateManager->findFeature('persistence-test');
        
        expect($feature->parentFeature)->toBe('../parent/_parent.md');
        expect($feature->parentRelationship)->toBe('Extends parent with testing');
        expect($feature->phase->value)->toBe('complete');
    });

    it('tracks code and test paths during execution', function () {
        $featurePath = $this->createFeatureFixture('tracking-test');
        
        // Transition to planning
        artisan('speedrun:feature-plan tracking-test --transition')->assertSuccessful();
        
        // Create planning documents with specific target files
        $this->createPlanningDocument($featurePath, 'controller', 'app/Http/Controllers/TrackingController.php');
        $this->createPlanningDocument($featurePath, 'test', 'tests/Feature/TrackingTest.php');
        
        // Execute
        artisan('speedrun:feature-execute tracking-test --transition')->assertSuccessful();
        
        // Verify paths were tracked
        $stateManager = app(\Iambateman\Speedrun\Services\FeatureStateManager::class);
        $feature = $stateManager->findFeature('tracking-test');
        
        expect($feature->codePaths)->toContain('app/Http/Controllers/TrackingController.php');
        expect($feature->testPaths)->toContain('tests/Feature/TrackingTest.php');
    });

    it('handles workflow interruption and resumption', function () {
        // Start workflow
        artisan('speedrun:feature interrupt-test')->assertSuccessful();
        
        // Simulate interruption by directly changing phase
        $stateManager = app(\Iambateman\Speedrun\Services\FeatureStateManager::class);
        $feature = $stateManager->findFeature('interrupt-test');
        $stateManager->transitionFeature($feature, \Iambateman\Speedrun\Enums\FeaturePhase::PLANNING);
        
        // Resume should pick up from planning phase
        artisan('speedrun:feature interrupt-test')
            ->expectsOutput("Resuming feature 'interrupt-test' in planning phase")
            ->assertSuccessful();
    });

    it('maintains timestamps throughout workflow', function () {
        artisan('speedrun:feature timestamp-test')->assertSuccessful();
        
        $stateManager = app(\Iambateman\Speedrun\Services\FeatureStateManager::class);
        $feature1 = $stateManager->findFeature('timestamp-test');
        $originalTimestamp = $feature1->lastUpdated;
        
        // Small delay to ensure timestamp difference
        usleep(10000);
        
        // Transition to next phase
        artisan('speedrun:feature-plan timestamp-test --transition')->assertSuccessful();
        
        $feature2 = $stateManager->findFeature('timestamp-test');
        expect($feature2->lastUpdated->isAfter($originalTimestamp))->toBeTrue();
    });

    it('validates phase transitions', function () {
        $featurePath = $this->createFeatureFixture('validation-test');
        
        // Try to skip to execution without planning documents
        artisan('speedrun:feature-plan validation-test --transition')->assertSuccessful();
        
        artisan('speedrun:feature-execute validation-test --transition')
            ->expectsOutput('No planning documents found')
            ->assertFailed();
    });

    it('handles feature with parent relationships', function () {
        // Create parent feature first
        $this->createCompletedFeatureFixture('parent-workflow');
        
        // Create child feature
        artisan('speedrun:feature child-workflow')
            ->expectsChoice('Does this feature have a parent feature?', '../parent-workflow/_parent-workflow.md', [
                'none' => 'No parent feature',
                '../parent-workflow/_parent-workflow.md' => 'parent-workflow'
            ])
            ->expectsQuestion('Describe the relationship to the parent feature:', 'Child of parent-workflow')
            ->assertSuccessful();
        
        // Complete workflow
        $featurePath = $this->testBasePath . '/wip/child-workflow';
        artisan('speedrun:feature-plan child-workflow --transition')->assertSuccessful();
        $this->createPlanningDocument($featurePath, 'controller');
        artisan('speedrun:feature-execute child-workflow --transition')->assertSuccessful();
        artisan('speedrun:feature-cleanup child-workflow')->assertSuccessful();
        
        // Verify relationship maintained
        $stateManager = app(\Iambateman\Speedrun\Services\FeatureStateManager::class);
        $feature = $stateManager->findFeature('child-workflow');
        expect($feature->parentFeature)->toBe('../parent-workflow/_parent-workflow.md');
    });

    it('creates proper directory structure at each phase', function () {
        artisan('speedrun:feature structure-workflow')->assertSuccessful();
        
        $wipPath = $this->testBasePath . '/wip/structure-workflow';
        expect($wipPath)->toHaveFeatureStructure();
        
        // Progress to completion
        artisan('speedrun:feature-plan structure-workflow --transition')->assertSuccessful();
        $this->createPlanningDocument($wipPath, 'controller');
        artisan('speedrun:feature-execute structure-workflow --transition')->assertSuccessful();
        
        // Should now be in features directory
        $completedPath = $this->testBasePath . '/features/structure-workflow';
        expect(file_exists($completedPath))->toBeTrue();
        expect(file_exists($wipPath))->toBeFalse(); // Original should be moved
    });

    it('handles cleanup decisions correctly', function () {
        $featurePath = $this->createFeatureFixture('cleanup-workflow');
        
        // Progress to cleanup
        artisan('speedrun:feature-plan cleanup-workflow --transition')->assertSuccessful();
        
        // Create multiple planning documents
        $this->createPlanningDocument($featurePath, 'controller');
        $this->createPlanningDocument($featurePath, 'model');  
        $this->createPlanningDocument($featurePath, 'test');
        
        artisan('speedrun:feature-execute cleanup-workflow --transition')->assertSuccessful();
        
        // Cleanup should ask which files to keep
        artisan('speedrun:feature-cleanup cleanup-workflow')
            ->expectsOutput('Found 3 planning documents')
            ->expectsChoice('Select planning documents to keep:', [], [
                'planning/_plan_controller.md' => '_plan_controller.md',
                'planning/_plan_model.md' => '_plan_model.md', 
                'planning/_plan_test.md' => '_plan_test.md'
            ])
            ->assertSuccessful();
        
        $this->assertFeatureInPhase('cleanup-workflow', 'complete', 'features');
    });

    it('supports workflow recovery from errors', function () {
        // Create feature with corrupted state
        $corruptedPath = $this->createCorruptedFeature('recovery-workflow');
        
        // Attempt to resume should offer recovery
        artisan('speedrun:feature recovery-workflow')
            ->expectsOutput('appears to be corrupted')
            ->assertFailed(); // Will fail due to corruption
            
        // After manual repair, should work
        // (In real implementation, this would be handled by recovery service)
    });

});