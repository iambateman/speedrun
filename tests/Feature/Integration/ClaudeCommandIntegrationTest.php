<?php

use Illuminate\Support\Facades\File;
use function Pest\Laravel\artisan;

describe('Claude Code Command Integration', function () {

    beforeEach(function () {
        // Set up Claude commands directory
        $this->claudeCommandsPath = $this->app->basePath('.claude/commands');
        if (!File::exists($this->claudeCommandsPath)) {
            File::makeDirectory($this->claudeCommandsPath, 0755, true);
        }
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
    });

    it('improve command has correct frontmatter', function () {
        artisan('vendor:publish --tag=speedrun-features-commands')->assertSuccessful();

        $content = File::get($this->claudeCommandsPath . '/improve.md');

        expect($content)->toContain('name: improve');
        expect($content)->toContain('description: Improve an existing completed feature');
        expect($content)->toContain('arguments: "[feature-name]"');
        expect($content)->toContain('php artisan speedrun:feature-improve $ARGUMENTS');
    });


});
