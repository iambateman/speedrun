<?php

namespace Tests\Feature\Commands;

use Iambateman\Speedrun\Commands\CodebaseDescribeCommand;
use Iambateman\Speedrun\Services\CodebaseAnalyzer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Mockery;

class CodebaseDescribeCommandTest extends TestCase
{
    /** @test */
    public function it_analyzes_codebase_and_generates_features()
    {
        // Mock the analyzer
        $analyzer = Mockery::mock(CodebaseAnalyzer::class);
        $this->app->instance(CodebaseAnalyzer::class, $analyzer);

        // Mock File facade
        File::shouldReceive('isDirectory')
            ->with(getcwd())
            ->andReturn(true);

        File::shouldReceive('isDirectory')
            ->with(base_path('features/discovered'))
            ->andReturn(false);

        File::shouldReceive('makeDirectory')
            ->with(base_path('features/discovered'), 0755, true)
            ->once();

        File::shouldReceive('isDirectory')
            ->with(base_path('features/discovered/user-management'))
            ->andReturn(false);

        File::shouldReceive('makeDirectory')
            ->with(base_path('features/discovered/user-management'), 0755, true)
            ->once();

        File::shouldReceive('exists')
            ->with(base_path('features/discovered/user-management/_user-management.md'))
            ->andReturn(false);

        File::shouldReceive('put')
            ->with(
                base_path('features/discovered/user-management/_user-management.md'),
                Mockery::type('string')
            )
            ->once();

        // Mock analysis result
        $features = collect([
            [
                'name' => 'user-management',
                'title' => 'User Management',
                'content' => "# User Management\n\n## Overview\nUser management functionality.",
                'code_paths' => ['/app/Http/Controllers/UserController.php'],
                'test_paths' => ['/tests/Feature/UserTest.php'],
                'routes' => [['path' => '/users', 'method' => 'GET']],
                'controllers' => [['class_name' => 'UserController']],
                'models' => [['class_name' => 'User']],
                'views' => [],
                'tests' => [['class_name' => 'UserTest']]
            ]
        ]);

        $analyzer->shouldReceive('analyzeCodebase')
            ->once()
            ->with(getcwd(), Mockery::type('array'))
            ->andReturn($features);

        $this->artisan(CodebaseDescribeCommand::class)
            ->expectsOutput('🚀 Speedrun Codebase Analyzer')
            ->expectsOutput('🔍 Analyzing codebase at: ' . getcwd())
            ->expectsOutput('✅ Analysis completed in')
            ->expectsOutput('📁 Created output directory: ' . base_path('features/discovered'))
            ->expectsOutput('📝 Generated: ' . base_path('features/discovered/user-management/_user-management.md'))
            ->expectsOutput('✨ Generated 1 feature files in ' . base_path('features/discovered'))
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_dry_run_option()
    {
        $analyzer = Mockery::mock(CodebaseAnalyzer::class);
        $this->app->instance(CodebaseAnalyzer::class, $analyzer);

        File::shouldReceive('isDirectory')
            ->with(getcwd())
            ->andReturn(true);

        // Should not create any directories or files in dry run
        File::shouldNotReceive('makeDirectory');
        File::shouldNotReceive('put');

        $features = collect([
            [
                'name' => 'user-management',
                'title' => 'User Management',
                'content' => "# User Management\n\n## Overview\nUser management functionality.",
                'code_paths' => [],
                'test_paths' => [],
                'routes' => [],
                'controllers' => [],
                'models' => [],
                'views' => [],
                'tests' => []
            ]
        ]);

        $analyzer->shouldReceive('analyzeCodebase')
            ->once()
            ->andReturn($features);

        $this->artisan(CodebaseDescribeCommand::class, ['--dry-run' => true])
            ->expectsOutput('🧪 Dry run completed. No files were created.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_analyzes_specific_feature()
    {
        $analyzer = Mockery::mock(CodebaseAnalyzer::class);
        $this->app->instance(CodebaseAnalyzer::class, $analyzer);

        File::shouldReceive('isDirectory')
            ->with(getcwd())
            ->andReturn(true);

        File::shouldReceive('isDirectory')
            ->with(base_path('features/discovered'))
            ->andReturn(true);

        File::shouldReceive('isDirectory')
            ->with(base_path('features/discovered/auth'))
            ->andReturn(false);

        File::shouldReceive('makeDirectory')
            ->with(base_path('features/discovered/auth'), 0755, true)
            ->once();

        File::shouldReceive('exists')
            ->with(base_path('features/discovered/auth/_auth.md'))
            ->andReturn(false);

        File::shouldReceive('put')
            ->with(
                base_path('features/discovered/auth/_auth.md'),
                Mockery::type('string')
            )
            ->once();

        $feature = [
            'name' => 'auth',
            'title' => 'Authentication',
            'content' => "# Authentication\n\n## Overview\nAuthentication functionality.",
            'code_paths' => ['/app/Http/Controllers/AuthController.php'],
            'test_paths' => ['/tests/Feature/AuthTest.php'],
            'routes' => [],
            'controllers' => [],
            'models' => [],
            'views' => [],
            'tests' => []
        ];

        $analyzer->shouldReceive('analyzeSpecificFeature')
            ->once()
            ->with(getcwd(), 'auth', Mockery::type('array'))
            ->andReturn($feature);

        $this->artisan(CodebaseDescribeCommand::class, ['--feature' => 'auth'])
            ->expectsOutput('📝 Generated: ' . base_path('features/discovered/auth/_auth.md'))
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_non_existent_directory()
    {
        File::shouldReceive('isDirectory')
            ->with('/non/existent/path')
            ->andReturn(false);

        $this->artisan(CodebaseDescribeCommand::class, ['target' => '/non/existent/path'])
            ->expectsOutput('Target directory does not exist: /non/existent/path')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_handles_empty_analysis_results()
    {
        $analyzer = Mockery::mock(CodebaseAnalyzer::class);
        $this->app->instance(CodebaseAnalyzer::class, $analyzer);

        File::shouldReceive('isDirectory')
            ->with(getcwd())
            ->andReturn(true);

        $analyzer->shouldReceive('analyzeCodebase')
            ->once()
            ->andReturn(collect());

        $this->artisan(CodebaseDescribeCommand::class)
            ->expectsOutput('No features could be extracted from the codebase.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_analysis_exceptions()
    {
        $analyzer = Mockery::mock(CodebaseAnalyzer::class);
        $this->app->instance(CodebaseAnalyzer::class, $analyzer);

        File::shouldReceive('isDirectory')
            ->with(getcwd())
            ->andReturn(true);

        $analyzer->shouldReceive('analyzeCodebase')
            ->once()
            ->andThrow(new \Exception('Analysis failed'));

        $this->artisan(CodebaseDescribeCommand::class)
            ->expectsOutput('Analysis failed: Analysis failed')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_prompts_before_overwriting_existing_files()
    {
        $analyzer = Mockery::mock(CodebaseAnalyzer::class);
        $this->app->instance(CodebaseAnalyzer::class, $analyzer);

        File::shouldReceive('isDirectory')
            ->with(getcwd())
            ->andReturn(true);

        File::shouldReceive('isDirectory')
            ->with(base_path('features/discovered'))
            ->andReturn(true);

        File::shouldReceive('isDirectory')
            ->with(base_path('features/discovered/existing-feature'))
            ->andReturn(true);

        File::shouldReceive('exists')
            ->with(base_path('features/discovered/existing-feature/_existing-feature.md'))
            ->andReturn(true);

        // Mock user declining to overwrite
        $this->expectsConfirmation('Feature file already exists: ' . base_path('features/discovered/existing-feature/_existing-feature.md') . '. Overwrite?', 'no');

        File::shouldNotReceive('put');

        $features = collect([
            [
                'name' => 'existing-feature',
                'title' => 'Existing Feature',
                'content' => "# Existing Feature\n\n## Overview\nExisting functionality.",
                'code_paths' => [],
                'test_paths' => [],
                'routes' => [],
                'controllers' => [],
                'models' => [],
                'views' => [],
                'tests' => []
            ]
        ]);

        $analyzer->shouldReceive('analyzeCodebase')
            ->once()
            ->andReturn($features);

        $this->artisan(CodebaseDescribeCommand::class)
            ->expectsOutput('✨ Generated 0 feature files in ' . base_path('features/discovered'))
            ->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}