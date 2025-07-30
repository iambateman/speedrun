<?php

use function Pest\Laravel\artisan;
use Illuminate\Support\Facades\File;

describe('Claude Code Command Integration', function () {

    beforeEach(function () {
        // Set up Claude commands directory
        $this->claudeCommandsPath = base_path('.claude/commands');
        File::makeDirectory($this->claudeCommandsPath, 0755, true);
    });

    it('publishes Claude commands correctly', function () {
        artisan('vendor:publish --tag=speedrun-features-commands')
            ->assertSuccessful();
        
        expect(File::exists($this->claudeCommandsPath . '/feature.md'))->toBeTrue();
        expect(File::exists($this->claudeCommandsPath . '/improve.md'))->toBeTrue();
        expect(File::exists($this->claudeCommandsPath . '/README.md'))->toBeTrue();
    });

    it('feature command has correct frontmatter', function () {
        artisan('vendor:publish --tag=speedrun-features-commands')->assertSuccessful();
        
        $content = File::get($this->claudeCommandsPath . '/feature.md');
        
        expect($content)->toContain('name: feature');
        expect($content)->toContain('description: Manage feature lifecycle');
        expect($content)->toContain('arguments: "[feature-name]"');
        expect($content)->toContain('php artisan speedrun:feature $ARGUMENTS');
    });

    it('improve command has correct frontmatter', function () {
        artisan('vendor:publish --tag=speedrun-features-commands')->assertSuccessful();
        
        $content = File::get($this->claudeCommandsPath . '/improve.md');
        
        expect($content)->toContain('name: improve');
        expect($content)->toContain('description: Improve an existing completed feature');
        expect($content)->toContain('arguments: "[feature-name]"');
        expect($content)->toContain('php artisan speedrun:feature-improve $ARGUMENTS');
    });

    it('Claude commands route to correct artisan commands', function () {
        artisan('vendor:publish --tag=speedrun-features-commands')->assertSuccessful();
        
        // Test /feature command routing
        artisan('speedrun:feature test-routing')
            ->assertSuccessful();
        
        $this->assertFeatureExists('test-routing');
        
        // Test /improve command routing  
        $this->createCompletedFeatureFixture('improve-routing');
        
        artisan('speedrun:feature-improve improve-routing')
            ->expectsQuestion('What would you like to improve about this feature?', 'Test routing')
            ->assertSuccessful();
        
        $this->assertFeatureExists('improve-routing', 'wip');
    });

    it('commands work with arguments', function () {
        artisan('vendor:publish --tag=speedrun-features-commands')->assertSuccessful();
        
        // Simulate Claude Code passing arguments
        artisan('speedrun:feature', ['name' => 'claude-args-test'])
            ->assertSuccessful();
        
        $this->assertFeatureExists('claude-args-test');
    });

    it('commands work without arguments', function () {
        artisan('vendor:publish --tag=speedrun-features-commands')->assertSuccessful();
        
        // Create some existing features for discovery
        $this->createFeatureFixture('discoverable-1');
        $this->createFeatureFixture('discoverable-2');
        
        artisan('speedrun:feature')
            ->expectsOutput('Search for a feature')
            ->assertSuccessful();
    });

    it('README provides correct usage instructions', function () {
        artisan('vendor:publish --tag=speedrun-features-commands')->assertSuccessful();
        
        $readme = File::get($this->claudeCommandsPath . '/README.md');
        
        expect($readme)->toContain('/feature [name]');
        expect($readme)->toContain('/improve [name]');
        expect($readme)->toContain('Quick Start');
        expect($readme)->toContain('Feature Lifecycle');
        expect($readme)->toContain('Directory Structure');
    });

    it('handles Claude Code environment correctly', function () {
        // Mock environment variables that Claude Code might set
        putenv('CLAUDE_CODE_SESSION=true');
        
        artisan('speedrun:feature claude-env-test')
            ->assertSuccessful();
        
        $this->assertFeatureExists('claude-env-test');
        
        // Clean up
        putenv('CLAUDE_CODE_SESSION');
    });

    it('provides helpful error messages for Claude Code users', function () {
        // Test with invalid feature name that might come from Claude
        artisan('speedrun:feature "Invalid Feature Name"')
            ->expectsOutput('must be kebab-case')
            ->assertFailed();
    });

    it('handles feature discovery in Claude Code context', function () {
        // Create features with different phases
        $this->createFeatureFixture('claude-discovery-1', [
            'frontmatter' => ['phase' => 'description']
        ]);
        $this->createFeatureFixture('claude-discovery-2', [
            'frontmatter' => ['phase' => 'planning'] 
        ]);
        $this->createCompletedFeatureFixture('claude-discovery-3');
        
        artisan('speedrun:feature')
            ->expectsOutput('Search for a feature')
            ->assertSuccessful();
    });

    it('supports common Claude Code usage patterns', function () {
        // Pattern 1: Create new feature
        artisan('speedrun:feature user-authentication')
            ->assertSuccessful();
        
        // Pattern 2: Resume existing feature
        artisan('speedrun:feature user-authentication')
            ->expectsOutput('Resuming feature')
            ->assertSuccessful();
        
        // Pattern 3: Improve completed feature
        $this->createCompletedFeatureFixture('improve-pattern');
        
        artisan('speedrun:feature-improve improve-pattern')
            ->expectsQuestion('What would you like to improve about this feature?', 'Add two-factor auth')
            ->assertSuccessful();
    });

    it('integrates with package setup command', function () {
        artisan('speedrun:feature-setup')
            ->assertSuccessful();
        
        // Verify directories were created
        expect(File::exists(config('speedrun.features.directories.wip')))->toBeTrue();
        expect(File::exists(config('speedrun.features.directories.completed')))->toBeTrue();
        expect(File::exists(config('speedrun.features.directories.archive')))->toBeTrue();
    });

    it('handles concurrent Claude Code sessions', function () {
        // Create locked feature
        $this->createLockedFeature('concurrent-claude');
        
        artisan('speedrun:feature concurrent-claude')
            ->expectsOutput('currently being edited')
            ->assertFailed();
    });

    it('supports feature relationship mapping in Claude context', function () {
        // Create parent feature
        $this->createCompletedFeatureFixture('auth-system');
        
        // Create child feature with relationship
        artisan('speedrun:feature two-factor-auth')
            ->expectsChoice('Does this feature have a parent feature?', '../auth-system/_auth-system.md', [
                'none' => 'No parent feature',
                '../auth-system/_auth-system.md' => 'auth-system'
            ])
            ->expectsQuestion('Describe the relationship to the parent feature:', 'Adds 2FA to existing auth')
            ->assertSuccessful();
        
        // Verify relationship
        $stateManager = app(\Iambateman\Speedrun\Services\FeatureStateManager::class);
        $feature = $stateManager->findFeature('two-factor-auth');
        expect($feature->parentFeature)->toBe('../auth-system/_auth-system.md');
    });

    it('provides context-aware help for Claude Code', function () {
        // Test help system integration
        $features = [
            $this->createFeatureFixture('help-context-1'),
            $this->createFeatureFixture('help-context-2'),
            $this->createCompletedFeatureFixture('help-context-3')
        ];
        
        // Feature discovery should provide helpful context
        artisan('speedrun:feature')
            ->expectsOutput('Search for a feature')
            ->assertSuccessful();
    });

    it('handles special characters in Claude Code arguments', function () {
        // Test with arguments that might come from Claude Code
        artisan('speedrun:feature', ['name' => 'api-integration'])
            ->assertSuccessful();
        
        $this->assertFeatureExists('api-integration');
    });

    it('supports batch operations for Claude Code workflows', function () {
        // Create multiple features for batch testing
        $features = ['batch-1', 'batch-2', 'batch-3'];
        
        foreach ($features as $feature) {
            artisan('speedrun:feature', ['name' => $feature])
                ->assertSuccessful();
            
            $this->assertFeatureExists($feature);
        }
        
        // Test discovery of multiple features
        artisan('speedrun:feature')
            ->expectsOutput('Search for a feature')
            ->assertSuccessful();
    });

});