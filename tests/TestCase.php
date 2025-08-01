<?php

namespace Iambateman\Speedrun\Tests;

use Iambateman\Speedrun\SpeedrunServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected string $testBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Iambateman\\Speedrun\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // testBasePath is already initialized in getEnvironmentSetUp
        $this->createTestDirectories();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestDirectories();
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            SpeedrunServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        // Initialize test base path early
        $this->testBasePath = sys_get_temp_dir().'/speedrun-features-test-'.uniqid();

        // Configure test directories for Speedrun package
        $app['config']->set('speedrun.directory', $this->testBasePath);

        // Configure test settings
        $app['config']->set('speedrun.installed', true); // Mark as installed for tests
        $app['config']->set('speedrun.features.locking.enabled', true);
        $app['config']->set('speedrun.features.locking.timeout_minutes', 1); // Short timeout for tests
        $app['config']->set('speedrun.features.transitions.require_confirmation', false); // Skip confirmations in tests
        $app['config']->set('speedrun.features.transitions.auto_commit', false);

        // Configure prompts for testing
        $app['config']->set('speedrun.features.prompts.max_search_results', 5);
        $app['config']->set('speedrun.features.prompts.require_input', false); // Skip prompts in tests

        /*
        $migration = include __DIR__.'/../database/migrations/create_speedrun_table.php.stub';
        $migration->up();
        */
    }

    protected function createTestDirectories(): void
    {
        $directories = [
            $this->testBasePath,
            $this->testBasePath.'/wip',
            $this->testBasePath.'/features',
        ];

        foreach ($directories as $dir) {
            if (! File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
        }
    }

    protected function cleanupTestDirectories(): void
    {
        if (File::exists($this->testBasePath)) {
            File::deleteDirectory($this->testBasePath);
        }
    }

    protected function createFeatureFixture(string $name, array $attributes = []): string
    {
        $path = $this->testBasePath.'/wip/'.$name;
        File::makeDirectory($path, 0755, true);

        // Create subdirectories
        File::makeDirectory($path.'/planning', 0755, true);
        File::makeDirectory($path.'/research', 0755, true);
        File::makeDirectory($path.'/assets', 0755, true);

        $frontmatter = array_merge([
            'phase' => 'description',
            'feature_name' => $name,
            'created_at' => '2025-07-30',
            'last_updated' => '2025-07-30',
            'test_paths' => [],
            'code_paths' => [],
            'artifacts' => [
                'planning_docs' => [],
                'research_files' => [],
                'assets' => [],
            ],
        ], $attributes['frontmatter'] ?? []);

        $content = $attributes['content'] ?? $this->getDefaultFeatureContent($name);

        $yaml = \Symfony\Component\Yaml\Yaml::dump($frontmatter, 4, 2);
        $featureContent = "---\n{$yaml}---\n\n{$content}";

        File::put($path.'/_'.$name.'.md', $featureContent);

        return $path;
    }

    protected function createCompletedFeatureFixture(string $name, array $attributes = []): string
    {
        $path = $this->testBasePath.'/features/'.$name;
        File::makeDirectory($path, 0755, true);

        // Create subdirectories
        File::makeDirectory($path.'/planning', 0755, true);
        File::makeDirectory($path.'/research', 0755, true);
        File::makeDirectory($path.'/assets', 0755, true);

        $frontmatter = array_merge([
            'phase' => 'complete',
            'feature_name' => $name,
            'created_at' => '2025-07-30',
            'last_updated' => '2025-07-30',
            'test_paths' => ['tests/Feature/'.ucfirst($name).'Test.php'],
            'code_paths' => ['app/Models/'.ucfirst($name).'.php'],
            'artifacts' => [
                'planning_docs' => [],
                'research_files' => [],
                'assets' => [],
            ],
        ], $attributes['frontmatter'] ?? []);

        $content = $attributes['content'] ?? $this->getDefaultFeatureContent($name);

        $yaml = \Symfony\Component\Yaml\Yaml::dump($frontmatter, 4, 2);
        $featureContent = "---\n{$yaml}---\n\n{$content}";

        File::put($path.'/_'.$name.'.md', $featureContent);

        return $path;
    }

    protected function createPlanningDocument(string $featurePath, string $type, ?string $targetFile = null): string
    {
        $planningDir = $featurePath.'/planning';
        if (! File::exists($planningDir)) {
            File::makeDirectory($planningDir, 0755, true);
        }

        $planningFile = $planningDir.'/_plan_'.$type.'.md';
        $targetFile = $targetFile ?? $this->getDefaultTargetFile($type);

        $content = <<<MD
# {$type} Planning Document

File: {$targetFile}

## Overview
Planning document for {$type} implementation.

```php
<?php

// Generated {$type} code would go here
class Generated{$type}
{
    public function example()
    {
        return 'This is generated code';
    }
}
```
MD;

        File::put($planningFile, $content);

        return $planningFile;
    }

    protected function createCorruptedFeature(string $name): string
    {
        $path = $this->testBasePath.'/wip/'.$name;
        File::makeDirectory($path, 0755, true);

        // Create invalid frontmatter
        $content = <<<'MD'
---
invalid: yaml: content:
    - broken
---

# Corrupted Feature

This feature has corrupted frontmatter.
MD;

        File::put($path.'/_{$name}.md', $content);

        return $path;
    }

    protected function createLockedFeature(string $name, bool $stale = false): string
    {
        $path = $this->createFeatureFixture($name);

        $lockTime = $stale ? now()->subHours(2) : now();
        $lockData = [
            'locked_at' => $lockTime->toIso8601String(),
            'locked_by' => 'test_user',
            'pid' => $stale ? 99999 : getmypid(),
        ];

        File::put($path.'/.lock', json_encode($lockData, JSON_PRETTY_PRINT));

        return $path;
    }

    private function getDefaultFeatureContent(string $name): string
    {
        $title = str_replace('-', ' ', ucwords($name, '-'));

        return <<<MD
# {$title}

## Overview
Test feature for {$name} functionality.

## Requirements
- Functional requirement 1
- Functional requirement 2
- Non-functional requirement 1

## Implementation Notes
Technical considerations and constraints.

## Dependencies
- Laravel Framework
- Other dependencies as needed

## Acceptance Criteria
- [ ] User can perform action A
- [ ] System validates input B
- [ ] Feature integrates with component C
MD;
    }

    private function getDefaultTargetFile(string $type): string
    {
        return match ($type) {
            'controller' => 'app/Http/Controllers/TestController.php',
            'model' => 'app/Models/TestModel.php',
            'migration' => 'database/migrations/2025_07_30_create_test_table.php',
            'test' => 'tests/Feature/TestFeatureTest.php',
            default => 'app/Generated/'.ucfirst($type).'.php',
        };
    }

    protected function assertFeatureExists(string $name, string $directory = 'wip'): void
    {
        $path = $this->testBasePath.'/'.$directory.'/'.$name;
        $this->assertTrue(File::exists($path), "Feature directory should exist: {$path}");
        $this->assertTrue(File::exists($path.'/_'.$name.'.md'), "Feature file should exist: {$path}/_".$name.'.md');
    }

    protected function assertFeatureDoesNotExist(string $name, string $directory = 'wip'): void
    {
        $path = $this->testBasePath.'/'.$directory.'/'.$name;
        $this->assertFalse(File::exists($path), "Feature directory should not exist: {$path}");
    }

    protected function assertFeatureInPhase(string $name, string $phase, string $directory = 'wip'): void
    {
        $path = $this->testBasePath.'/'.$directory.'/'.$name.'/_'.$name.'.md';
        $this->assertTrue(File::exists($path), "Feature file should exist: {$path}");

        $content = File::get($path);
        $this->assertStringContainsString("phase: {$phase}", $content);
    }
}
