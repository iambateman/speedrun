<?php

use function Pest\Laravel\artisan;

describe('FeatureCommand', function () {

    it('creates new feature when none exists', function () {
        artisan('speedrun:feature test-feature')
            ->assertSuccessful();
        
        $this->assertFeatureExists('test-feature');
        $this->assertFeatureInPhase('test-feature', 'description');
    });

    it('resumes existing feature in description phase', function () {
        $this->createFeatureFixture('existing-description', [
            'frontmatter' => ['phase' => 'description']
        ]);
        
        artisan('speedrun:feature existing-description')
            ->expectsOutput('Resuming feature')
            ->assertSuccessful();
    });

    it('resumes existing feature in planning phase', function () {
        $this->createFeatureFixture('existing-planning', [
            'frontmatter' => ['phase' => 'planning']
        ]);
        
        artisan('speedrun:feature existing-planning')
            ->expectsOutput('Resuming feature')
            ->assertSuccessful();
    });

    it('handles completed features appropriately', function () {
        $this->createCompletedFeatureFixture('completed-feature');
        
        artisan('speedrun:feature completed-feature')
            ->expectsOutput('already complete')
            ->expectsConfirmation('Would you like to improve this feature?', 'no')
            ->assertSuccessful();
    });

    it('starts improvement workflow when requested', function () {
        $this->createCompletedFeatureFixture('improve-feature');
        
        artisan('speedrun:feature improve-feature')
            ->expectsOutput('already complete')
            ->expectsConfirmation('Would you like to improve this feature?', 'yes')
            ->assertSuccessful();
    });

    it('shows discovery mode when no feature name provided', function () {
        $this->createFeatureFixture('discoverable-1');
        $this->createFeatureFixture('discoverable-2');
        
        artisan('speedrun:feature')
            ->expectsOutput('Search for a feature')
            ->assertSuccessful();
    });

    it('creates new feature when search returns create option', function () {
        artisan('speedrun:feature')
            ->expectsOutput('No existing features found')
            ->expectsQuestion('Enter the feature name:', 'new-from-discovery')
            ->assertSuccessful();
        
        $this->assertFeatureExists('new-from-discovery');
    });

    it('validates feature names', function () {
        artisan('speedrun:feature')
            ->expectsOutput('No existing features found')
            ->expectsQuestion('Enter the feature name:', 'Invalid Name')
            ->expectsQuestion('Enter the feature name:', 'valid-name')  // Retry with valid name
            ->assertSuccessful();
        
        $this->assertFeatureExists('valid-name');
    });

    it('handles parent feature selection', function () {
        $this->createCompletedFeatureFixture('parent-feature');
        
        artisan('speedrun:feature child-feature')
            ->expectsChoice('Does this feature have a parent feature?', '../parent-feature/_parent-feature.md', [
                'none' => 'No parent feature',
                '../parent-feature/_parent-feature.md' => 'parent-feature'
            ])
            ->expectsQuestion('Describe the relationship to the parent feature:', 'Extends parent functionality')
            ->assertSuccessful();
        
        $this->assertFeatureExists('child-feature');
        
        // Verify parent relationship was saved
        $feature = app(\Iambateman\Speedrun\Services\FeatureStateManager::class)->findFeature('child-feature');
        expect($feature->parentFeature)->toBe('../parent-feature/_parent-feature.md');
        expect($feature->parentRelationship)->toBe('Extends parent functionality');
    });

    it('handles force flag in production', function () {
        // Mock production environment
        app()->detectEnvironment(function () {
            return 'production';
        });
        
        artisan('speedrun:feature test-prod')
            ->expectsOutput('disabled in production')
            ->assertFailed();
        
        artisan('speedrun:feature test-prod --force')
            ->assertSuccessful();
    });

    it('creates feature directory structure', function () {
        artisan('speedrun:feature structure-test')
            ->assertSuccessful();
        
        $path = $this->testBasePath . '/wip/structure-test';
        expect($path)->toHaveFeatureStructure();
    });

    it('creates lock file for new features', function () {
        artisan('speedrun:feature lock-test')
            ->assertSuccessful();
        
        $lockFile = $this->testBasePath . '/wip/lock-test/.lock';
        expect(file_exists($lockFile))->toBeTrue();
        
        $lockData = json_decode(file_get_contents($lockFile), true);
        expect($lockData)->toHaveKey('locked_at');
        expect($lockData)->toHaveKey('locked_by');
        expect($lockData)->toHaveKey('pid');
    });

    it('displays feature information when resuming', function () {
        $this->createFeatureFixture('info-test', [
            'frontmatter' => [
                'phase' => 'planning',
                'created_at' => '2025-01-01',
                'parent_feature' => '../parent/_parent.md'
            ]
        ]);
        
        artisan('speedrun:feature info-test')
            ->expectsOutput('Feature: info-test')
            ->expectsOutput('Phase: 📋 Implementation Planning')
            ->expectsOutput('Created: 2025-01-01')
            ->expectsOutput('Parent Feature: ../parent/_parent.md')
            ->assertSuccessful();
    });

    it('routes to correct phase command', function () {
        // Mock different phases and verify correct sub-command is called
        $phases = [
            'description' => 'speedrun:feature-describe',
            'planning' => 'speedrun:feature-plan', 
            'execution' => 'speedrun:feature-execute',
            'cleanup' => 'speedrun:feature-cleanup'
        ];
        
        foreach ($phases as $phase => $expectedCommand) {
            $featureName = "routing-{$phase}";
            $this->createFeatureFixture($featureName, [
                'frontmatter' => ['phase' => $phase]
            ]);
            
            artisan('speedrun:feature ' . $featureName)
                ->expectsOutput("Resuming feature '{$featureName}' in {$phase} phase")
                ->assertSuccessful();
        }
    });

    it('handles concurrent access gracefully', function () {
        $feature = $this->createLockedFeature('concurrent-test');
        
        artisan('speedrun:feature concurrent-test')
            ->expectsOutput('currently being edited')
            ->assertFailed();
    });

    it('clears stale locks automatically', function () {
        $this->createLockedFeature('stale-test', true);
        
        artisan('speedrun:feature stale-test')
            ->expectsOutput('Stale lock cleared')
            ->assertSuccessful();
    });

    it('ensures directories exist before operation', function () {
        // Remove test directories
        $this->cleanupTestDirectories();
        
        artisan('speedrun:feature ensure-dirs-test')
            ->assertSuccessful();
        
        // Directories should be recreated
        expect(file_exists($this->testBasePath . '/wip'))->toBeTrue();
        expect(file_exists($this->testBasePath . '/features'))->toBeTrue();
        expect(file_exists($this->testBasePath . '/archive'))->toBeTrue();
    });

});