<?php

use function Pest\Laravel\artisan;

describe('Feature Improvement Workflow', function () {

    it('moves completed feature back to wip for improvement', function () {
        // Create completed feature
        $this->createCompletedFeatureFixture('improve-basic');
        
        artisan('speedrun:feature-improve improve-basic')
            ->expectsQuestion('What would you like to improve about this feature?', 'Add validation and error handling')
            ->assertSuccessful();
        
        // Feature should be moved back to wip directory
        $this->assertFeatureExists('improve-basic', 'wip');
        $this->assertFeatureDoesNotExist('improve-basic', 'features');
        $this->assertFeatureInPhase('improve-basic', 'description', 'wip');
    });

    it('records improvement goals', function () {
        $this->createCompletedFeatureFixture('improve-tracking');
        
        artisan('speedrun:feature-improve improve-tracking')
            ->expectsQuestion('What would you like to improve about this feature?', 'Improve performance with caching')
            ->assertSuccessful();
        
        // Verify improvement was recorded
        $stateManager = app(\Iambateman\Speedrun\Services\FeatureStateManager::class);
        $feature = $stateManager->findFeature('improve-tracking');
        
        expect($feature->improvementHistory)->toHaveCount(1);
        expect($feature->improvementHistory[0]['goal'])->toBe('Improve performance with caching');
        expect($feature->improvementHistory[0]['completed'])->toBeFalse();
    });

    it('allows searching for features to improve', function () {
        $this->createCompletedFeatureFixture('searchable-1');
        $this->createCompletedFeatureFixture('searchable-2'); 
        $this->createCompletedFeatureFixture('searchable-3');
        
        // Don't include WIP features in search
        $this->createFeatureFixture('wip-feature');
        
        artisan('speedrun:feature-improve')
            ->expectsOutput('Search for a feature to improve')
            ->assertSuccessful();
    });

    it('shows no features message when none completed', function () {
        // Only create WIP features
        $this->createFeatureFixture('wip-only-1');
        $this->createFeatureFixture('wip-only-2');
        
        artisan('speedrun:feature-improve')
            ->expectsOutput('No completed features found')
            ->assertFailed();
    });

    it('allows multiple improvements to same feature', function () {
        $this->createCompletedFeatureFixture('multi-improve');
        
        // First improvement
        artisan('speedrun:feature-improve multi-improve')
            ->expectsQuestion('What would you like to improve about this feature?', 'Add tests')
            ->assertSuccessful();
        
        // Complete the improvement workflow
        $featurePath = $this->testBasePath . '/wip/multi-improve';
        artisan('speedrun:feature-plan multi-improve --transition')->assertSuccessful();
        $this->createPlanningDocument($featurePath, 'test');
        artisan('speedrun:feature-execute multi-improve --transition')->assertSuccessful();
        artisan('speedrun:feature-cleanup multi-improve')->assertSuccessful();
        
        // Second improvement
        artisan('speedrun:feature-improve multi-improve')
            ->expectsQuestion('What would you like to improve about this feature?', 'Refactor for better performance')
            ->assertSuccessful();
        
        // Verify both improvements recorded
        $stateManager = app(\Iambateman\Speedrun\Services\FeatureStateManager::class);
        $feature = $stateManager->findFeature('multi-improve');
        
        expect($feature->improvementHistory)->toHaveCount(2);
        expect($feature->improvementHistory[0]['completed'])->toBeTrue();
        expect($feature->improvementHistory[1]['completed'])->toBeFalse();
    });

    it('preserves original feature data during improvement', function () {
        $this->createCompletedFeatureFixture('preserve-data', [
            'frontmatter' => [
                'parent_feature' => '../parent/_parent.md',
                'test_paths' => ['tests/Feature/OriginalTest.php'],
                'code_paths' => ['app/Models/Original.php'],
            ]
        ]);
        
        artisan('speedrun:feature-improve preserve-data')
            ->expectsQuestion('What would you like to improve about this feature?', 'Add more features')
            ->assertSuccessful();
        
        $stateManager = app(\Iambateman\Speedrun\Services\FeatureStateManager::class);
        $feature = $stateManager->findFeature('preserve-data');
        
        // Original data should be preserved
        expect($feature->parentFeature)->toBe('../parent/_parent.md');
        expect($feature->testPaths)->toContain('tests/Feature/OriginalTest.php');
        expect($feature->codePaths)->toContain('app/Models/Original.php');
    });

    it('resets phase to description for improvement', function () {
        $this->createCompletedFeatureFixture('phase-reset', [
            'frontmatter' => ['phase' => 'complete']
        ]);
        
        artisan('speedrun:feature-improve phase-reset')
            ->expectsQuestion('What would you like to improve about this feature?', 'Improve UX')
            ->assertSuccessful();
        
        $this->assertFeatureInPhase('phase-reset', 'description', 'wip');
    });

    it('handles improvement workflow end-to-end', function () {
        // Create and complete initial feature
        $this->createCompletedFeatureFixture('end-to-end-improve');
        
        // Start improvement
        artisan('speedrun:feature-improve end-to-end-improve')
            ->expectsQuestion('What would you like to improve about this feature?', 'Add API endpoints')
            ->assertSuccessful();
        
        // Progress through improvement workflow
        $featurePath = $this->testBasePath . '/wip/end-to-end-improve';
        artisan('speedrun:feature-plan end-to-end-improve --transition')->assertSuccessful();
        
        // Create improvement planning documents
        $this->createPlanningDocument($featurePath, 'api-controller', 'app/Http/Controllers/Api/ImproveController.php');
        
        artisan('speedrun:feature-execute end-to-end-improve --transition')->assertSuccessful();
        artisan('speedrun:feature-cleanup end-to-end-improve')->assertSuccessful();
        
        // Verify completion
        $this->assertFeatureInPhase('end-to-end-improve', 'complete', 'features');
        
        // Verify improvement was marked complete
        $stateManager = app(\Iambateman\Speedrun\Services\FeatureStateManager::class);
        $feature = $stateManager->findFeature('end-to-end-improve');
        expect($feature->improvementHistory[0]['completed'])->toBeTrue();
    });

    it('validates improvement reasons', function () {
        $this->createCompletedFeatureFixture('validate-reason');
        
        artisan('speedrun:feature-improve validate-reason')
            ->expectsQuestion('What would you like to improve about this feature?', '') // Empty response
            ->expectsQuestion('What would you like to improve about this feature?', 'Valid improvement reason') // Retry
            ->assertSuccessful();
    });

    it('shows improvement context in feature info', function () {
        $this->createCompletedFeatureFixture('show-context');
        
        artisan('speedrun:feature-improve show-context')
            ->expectsQuestion('What would you like to improve about this feature?', 'Add caching layer')
            ->expectsOutput('Feature moved back to work-in-progress')
            ->expectsOutput('Improvement goal: Add caching layer')
            ->assertSuccessful();
    });

    it('handles feature with existing improvements', function () {
        $this->createCompletedFeatureFixture('existing-improvements', [
            'frontmatter' => [
                'improvement_history' => [
                    [
                        'date' => '2025-01-01',
                        'goal' => 'Previous improvement',
                        'completed' => true
                    ]
                ]
            ]
        ]);
        
        artisan('speedrun:feature-improve existing-improvements')
            ->expectsQuestion('What would you like to improve about this feature?', 'New improvement')
            ->assertSuccessful();
        
        $stateManager = app(\Iambateman\Speedrun\Services\FeatureStateManager::class);
        $feature = $stateManager->findFeature('existing-improvements');
        
        expect($feature->improvementHistory)->toHaveCount(2);
        expect($feature->improvementHistory[0]['goal'])->toBe('Previous improvement');
        expect($feature->improvementHistory[1]['goal'])->toBe('New improvement');
    });

    it('maintains feature artifacts during improvement', function () {
        $completedPath = $this->createCompletedFeatureFixture('artifacts-improve');
        
        // Add some artifacts to completed feature
        file_put_contents($completedPath . '/research/important-notes.md', 'Important research');
        
        artisan('speedrun:feature-improve artifacts-improve')
            ->expectsQuestion('What would you like to improve about this feature?', 'Enhance functionality')
            ->assertSuccessful();
        
        $wipPath = $this->testBasePath . '/wip/artifacts-improve';
        expect(file_exists($wipPath . '/research/important-notes.md'))->toBeTrue();
    });

    it('allows canceling improvement selection', function () {
        $this->createCompletedFeatureFixture('cancel-improve');
        
        // Simulate user canceling search/selection
        // (This would depend on how Laravel Prompts handles cancellation)
        artisan('speedrun:feature-improve')
            ->expectsOutput('Search for a feature to improve')
            ->assertSuccessful(); // Should handle cancellation gracefully
    });

});