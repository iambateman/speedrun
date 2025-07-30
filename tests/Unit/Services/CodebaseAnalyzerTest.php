<?php

namespace Tests\Unit\Services;

use Iambateman\Speedrun\Services\CodebaseAnalyzer;
use Iambateman\Speedrun\Services\RouteAnalyzer;
use Iambateman\Speedrun\Services\ControllerAnalyzer;
use Iambateman\Speedrun\Services\ModelAnalyzer;
use Iambateman\Speedrun\Services\TestAnalyzer;
use Iambateman\Speedrun\Services\ViewAnalyzer;
use Iambateman\Speedrun\Services\FeatureGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Mockery;

class CodebaseAnalyzerTest extends TestCase
{
    private CodebaseAnalyzer $analyzer;
    private $routeAnalyzer;
    private $controllerAnalyzer;
    private $modelAnalyzer;
    private $testAnalyzer;
    private $viewAnalyzer;
    private $featureGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routeAnalyzer = Mockery::mock(RouteAnalyzer::class);
        $this->controllerAnalyzer = Mockery::mock(ControllerAnalyzer::class);
        $this->modelAnalyzer = Mockery::mock(ModelAnalyzer::class);
        $this->testAnalyzer = Mockery::mock(TestAnalyzer::class);
        $this->viewAnalyzer = Mockery::mock(ViewAnalyzer::class);
        $this->featureGenerator = Mockery::mock(FeatureGenerator::class);

        $this->analyzer = new CodebaseAnalyzer(
            $this->routeAnalyzer,
            $this->controllerAnalyzer,
            $this->modelAnalyzer,
            $this->testAnalyzer,
            $this->viewAnalyzer,
            $this->featureGenerator
        );
    }

    /** @test */
    public function it_analyzes_codebase_and_returns_features()
    {
        // Mock File facade
        File::shouldReceive('isDirectory')
            ->with('/test/path/routes')
            ->andReturn(true);

        File::shouldReceive('exists')
            ->with('/test/path/routes/web.php')
            ->andReturn(true);

        File::shouldReceive('exists')
            ->with('/test/path/routes/api.php')
            ->andReturn(false);

        File::shouldReceive('glob')
            ->with('/test/path/routes/*.php')
            ->andReturn(['/test/path/routes/web.php']);

        File::shouldReceive('isDirectory')
            ->with('/test/path/app/Http/Controllers')
            ->andReturn(true);

        File::shouldReceive('allFiles')
            ->andReturn(collect([
                (object)['getPathname' => '/test/path/app/Http/Controllers/UserController.php', 'getRelativePathname' => 'UserController.php']
            ]));

        File::shouldReceive('isDirectory')
            ->with('/test/path/app/Models')
            ->andReturn(false);

        File::shouldReceive('isDirectory')
            ->with('/test/path/app')
            ->andReturn(true);

        File::shouldReceive('glob')
            ->with('/test/path/app/*.php')
            ->andReturn(['/test/path/app/User.php']);

        File::shouldReceive('get')
            ->with('/test/path/app/User.php')
            ->andReturn('<?php class User extends Model {}');

        File::shouldReceive('isDirectory')
            ->with('/test/path/tests')
            ->andReturn(false);

        File::shouldReceive('isDirectory')
            ->with('/test/path/resources/views')
            ->andReturn(false);

        // Mock analyzer responses
        $routes = collect([['path' => '/users', 'method' => 'GET']]);
        $controllers = collect([['class_name' => 'UserController']]);
        $models = collect([['class_name' => 'User']]);
        $tests = collect();
        $views = collect();

        $this->routeAnalyzer->shouldReceive('analyze')
            ->once()
            ->andReturn($routes);

        $this->controllerAnalyzer->shouldReceive('analyze')
            ->once()
            ->andReturn($controllers);

        $this->modelAnalyzer->shouldReceive('analyze')
            ->once()
            ->andReturn($models);

        $this->testAnalyzer->shouldReceive('analyze')
            ->once()
            ->andReturn($tests);

        $this->viewAnalyzer->shouldReceive('analyze')
            ->once()
            ->andReturn($views);

        $expectedFeatures = collect([
            ['name' => 'user-management', 'title' => 'User Management']
        ]);

        $this->featureGenerator->shouldReceive('generateFeatures')
            ->once()
            ->with([
                'routes' => $routes,
                'controllers' => $controllers,
                'models' => $models,
                'tests' => $tests,
                'views' => $views
            ], Mockery::any())
            ->andReturn($expectedFeatures);

        $result = $this->analyzer->analyzeCodebase('/test/path');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals($expectedFeatures, $result);
    }

    /** @test */
    public function it_analyzes_specific_feature()
    {
        File::shouldReceive('isDirectory')->andReturn(true);
        File::shouldReceive('exists')->andReturn(true);
        File::shouldReceive('glob')->andReturn([]);
        File::shouldReceive('allFiles')->andReturn(collect());

        $this->routeAnalyzer->shouldReceive('analyze')->andReturn(collect());
        $this->controllerAnalyzer->shouldReceive('analyze')->andReturn(collect());
        $this->modelAnalyzer->shouldReceive('analyze')->andReturn(collect());
        $this->testAnalyzer->shouldReceive('analyze')->andReturn(collect());
        $this->viewAnalyzer->shouldReceive('analyze')->andReturn(collect());

        $features = collect([
            ['name' => 'user-management', 'title' => 'User Management'],
            ['name' => 'auth', 'title' => 'Authentication']
        ]);

        $this->featureGenerator->shouldReceive('generateFeatures')
            ->andReturn($features);

        $result = $this->analyzer->analyzeSpecificFeature('/test/path', 'user-management');

        $this->assertEquals(['name' => 'user-management', 'title' => 'User Management'], $result);
    }

    /** @test */
    public function it_returns_null_for_non_existent_specific_feature()
    {
        File::shouldReceive('isDirectory')->andReturn(true);
        File::shouldReceive('exists')->andReturn(true);
        File::shouldReceive('glob')->andReturn([]);
        File::shouldReceive('allFiles')->andReturn(collect());

        $this->routeAnalyzer->shouldReceive('analyze')->andReturn(collect());
        $this->controllerAnalyzer->shouldReceive('analyze')->andReturn(collect());
        $this->modelAnalyzer->shouldReceive('analyze')->andReturn(collect());
        $this->testAnalyzer->shouldReceive('analyze')->andReturn(collect());
        $this->viewAnalyzer->shouldReceive('analyze')->andReturn(collect());

        $features = collect([
            ['name' => 'auth', 'title' => 'Authentication']
        ]);

        $this->featureGenerator->shouldReceive('generateFeatures')
            ->andReturn($features);

        $result = $this->analyzer->analyzeSpecificFeature('/test/path', 'non-existent');

        $this->assertNull($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}