<?php

namespace Tests\Unit\Services;

use Iambateman\Speedrun\Services\RouteAnalyzer;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RouteAnalyzerTest extends TestCase
{
    private RouteAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new RouteAnalyzer();
    }

    /** @test */
    public function it_parses_basic_get_route()
    {
        $routeContent = "<?php\nRoute::get('/users', [UserController::class, 'index']);";
        
        File::shouldReceive('exists')
            ->with('/test/routes/web.php')
            ->andReturn(true);

        File::shouldReceive('get')
            ->with('/test/routes/web.php')
            ->andReturn($routeContent);

        $routes = $this->analyzer->analyze(['/test/routes/web.php']);

        $this->assertCount(1, $routes);
        $route = $routes->first();
        
        $this->assertEquals('GET', $route['method']);
        $this->assertEquals('/users', $route['path']);
        $this->assertEquals('UserController', $route['controller']);
        $this->assertEquals('index', $route['action']);
    }

    /** @test */
    public function it_parses_post_route_with_string_format()
    {
        $routeContent = "<?php\nRoute::post('/users', 'UserController@store');";
        
        File::shouldReceive('exists')
            ->with('/test/routes/web.php')
            ->andReturn(true);

        File::shouldReceive('get')
            ->with('/test/routes/web.php')
            ->andReturn($routeContent);

        $routes = $this->analyzer->analyze(['/test/routes/web.php']);

        $this->assertCount(1, $routes);
        $route = $routes->first();
        
        $this->assertEquals('POST', $route['method']);
        $this->assertEquals('/users', $route['path']);
        $this->assertEquals('UserController', $route['controller']);
        $this->assertEquals('store', $route['action']);
    }

    /** @test */
    public function it_parses_resource_route()
    {
        $routeContent = "<?php\nRoute::resource('users', UserController::class);";
        
        File::shouldReceive('exists')
            ->with('/test/routes/web.php')
            ->andReturn(true);

        File::shouldReceive('get')
            ->with('/test/routes/web.php')
            ->andReturn($routeContent);

        $routes = $this->analyzer->analyze(['/test/routes/web.php']);

        $this->assertCount(1, $routes);
        $route = $routes->first();
        
        $this->assertEquals('resource', $route['type']);
        $this->assertEquals('users', $route['path']);
        $this->assertEquals('UserController', $route['controller']);
        $this->assertContains('index', $route['methods']);
        $this->assertContains('store', $route['methods']);
        $this->assertContains('show', $route['methods']);
    }

    /** @test */
    public function it_parses_api_resource_route()
    {
        $routeContent = "<?php\nRoute::apiResource('users', UserController::class);";
        
        File::shouldReceive('exists')
            ->with('/test/routes/web.php')
            ->andReturn(true);

        File::shouldReceive('get')
            ->with('/test/routes/web.php')
            ->andReturn($routeContent);

        $routes = $this->analyzer->analyze(['/test/routes/web.php']);

        $this->assertCount(1, $routes);
        $route = $routes->first();
        
        $this->assertEquals('api_resource', $route['type']);
        $this->assertEquals('users', $route['path']);
        $this->assertEquals('UserController', $route['controller']);
        $this->assertContains('index', $route['methods']);
        $this->assertContains('store', $route['methods']);
        $this->assertNotContains('create', $route['methods']); // API resources don't have create/edit
        $this->assertNotContains('edit', $route['methods']);
    }

    /** @test */
    public function it_handles_non_existent_files()
    {
        File::shouldReceive('exists')
            ->with('/test/routes/nonexistent.php')
            ->andReturn(false);

        $routes = $this->analyzer->analyze(['/test/routes/nonexistent.php']);

        $this->assertCount(0, $routes);
    }

    /** @test */
    public function it_parses_multiple_route_files()
    {
        $webContent = "<?php\nRoute::get('/home', [HomeController::class, 'index']);";
        $apiContent = "<?php\nRoute::apiResource('users', UserController::class);";
        
        File::shouldReceive('exists')
            ->with('/test/routes/web.php')
            ->andReturn(true);

        File::shouldReceive('exists')
            ->with('/test/routes/api.php')
            ->andReturn(true);

        File::shouldReceive('get')
            ->with('/test/routes/web.php')
            ->andReturn($webContent);

        File::shouldReceive('get')
            ->with('/test/routes/api.php')
            ->andReturn($apiContent);

        $routes = $this->analyzer->analyze([
            '/test/routes/web.php',
            '/test/routes/api.php'
        ]);

        $this->assertCount(2, $routes);
        
        $webRoute = $routes->first();
        $this->assertEquals('GET', $webRoute['method']);
        $this->assertEquals('/home', $webRoute['path']);
        
        $apiRoute = $routes->last();
        $this->assertEquals('api_resource', $apiRoute['type']);
        $this->assertEquals('users', $apiRoute['path']);
    }
}