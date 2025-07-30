<?php

use function Pest\Laravel\artisan;

describe('InstallCommand', function () {

    it('installs the package with default directories', function () {
        // Override config to not be installed
        config(['speedrun.installed' => false]);
        
        artisan('speedrun:install')
            ->expectsQuestion('Base directory for features', '_docs')
            ->expectsConfirmation('Create these directories and install Speedrun?', 'yes')
            ->assertSuccessful();
    });

    it('shows already installed message when package is installed', function () {
        // Package is installed by default in TestCase
        artisan('speedrun:install')
            ->expectsOutput('Speedrun is already installed. Use --force to reinstall.')
            ->assertSuccessful();
    });

    it('allows force reinstall', function () {
        artisan('speedrun:install --force')
            ->expectsQuestion('Base directory for features', '_docs')
            ->expectsConfirmation('Create these directories and install Speedrun?', 'yes')
            ->assertSuccessful();
    });

    it('allows canceling installation', function () {
        config(['speedrun.installed' => false]);
        
        artisan('speedrun:install')
            ->expectsQuestion('Base directory for features', '_docs')
            ->expectsConfirmation('Create these directories and install Speedrun?', 'no')
            ->expectsOutput('Installation cancelled.')
            ->assertSuccessful();
    });

    it('accepts custom directory configuration', function () {
        config(['speedrun.installed' => false]);
        
        $customDir = 'custom-features';
        
        artisan('speedrun:install')
            ->expectsQuestion('Base directory for features', $customDir)
            ->expectsConfirmation('Create these directories and install Speedrun?', 'yes')
            ->expectsOutput('Creating directories...')
            ->expectsOutput('🎉 Speedrun installation complete!')
            ->assertSuccessful();
    });

});