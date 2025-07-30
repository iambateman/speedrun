<?php

namespace Tests\Unit\Services;

use Iambateman\Speedrun\Services\FeatureGenerator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class FeatureGeneratorTest extends TestCase
{
    private FeatureGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new FeatureGenerator();
    }

    /** @test */
    public function it_generates_features_from_analysis_data()
    {
        $analysisData = [
            'routes' => collect([
                [
                    'path' => '/users',
                    'method' => 'GET',
                    'controller' => 'UserController',
                    'action' => 'index',
                    'file' => '/test/routes/web.php'
                ],
                [
                    'path' => '/users',
                    'method' => 'POST',
                    'controller' => 'UserController',
                    'action' => 'store',
                    'file' => '/test/routes/web.php'
                ]
            ]),
            'controllers' => collect([
                [
                    'class_name' => 'UserController',
                    'file' => '/test/app/Http/Controllers/UserController.php',
                    'methods' => [
                        'index' => ['uses_models' => ['User']],
                        'store' => ['uses_models' => ['User'], 'validates_request' => true]
                    ],
                    'view_usage' => ['users.index', 'users.create'],
                    'middleware' => [],
                    'database_interactions' => ['eloquent_queries']
                ]
            ]),
            'models' => collect([
                [
                    'class_name' => 'User',
                    'file' => '/test/app/Models/User.php',
                    'table_name' => 'users',
                    'relationships' => []
                ]
            ]),
            'tests' => collect([
                [
                    'class_name' => 'UserControllerTest',
                    'file' => '/test/tests/Feature/UserControllerTest.php',
                    'tested_routes' => ['/users'],
                    'tested_models' => ['User']
                ]
            ]),
            'views' => collect([
                [
                    'view_name' => 'users.index',
                    'file' => '/test/resources/views/users/index.blade.php'
                ]
            ])
        ];

        $features = $this->generator->generateFeatures($analysisData);

        $this->assertInstanceOf(Collection::class, $features);
        $this->assertGreaterThan(0, $features->count());
        
        $feature = $features->first();
        $this->assertArrayHasKey('name', $feature);
        $this->assertArrayHasKey('title', $feature);
        $this->assertArrayHasKey('content', $feature);
        $this->assertArrayHasKey('code_paths', $feature);
        $this->assertArrayHasKey('test_paths', $feature);
    }

    /** @test */
    public function it_groups_related_components_into_features()
    {
        $analysisData = [
            'routes' => collect([
                [
                    'path' => '/users',
                    'method' => 'GET',
                    'controller' => 'UserController',
                    'action' => 'index',
                    'file' => '/test/routes/web.php'
                ]
            ]),
            'controllers' => collect([
                [
                    'class_name' => 'UserController',
                    'file' => '/test/app/Http/Controllers/UserController.php',
                    'methods' => [
                        'index' => ['uses_models' => ['User']]
                    ],
                    'view_usage' => [],
                    'middleware' => [],
                    'database_interactions' => []
                ]
            ]),
            'models' => collect([
                [
                    'class_name' => 'User',
                    'file' => '/test/app/Models/User.php'
                ]
            ]),
            'tests' => collect(),
            'views' => collect()
        ];

        $features = $this->generator->generateFeatures($analysisData);

        $this->assertCount(1, $features);
        
        $feature = $features->first();
        $this->assertEquals('users', $feature['name']);
        $this->assertCount(1, $feature['routes']);
        $this->assertCount(1, $feature['controllers']);
        $this->assertCount(1, $feature['models']);
    }

    /** @test */
    public function it_generates_proper_feature_content()
    {
        $analysisData = [
            'routes' => collect([
                [
                    'path' => '/users',
                    'method' => 'GET',
                    'controller' => 'UserController',
                    'action' => 'index',
                    'file' => '/test/routes/web.php'
                ]
            ]),
            'controllers' => collect([
                [
                    'class_name' => 'UserController',
                    'file' => '/test/app/Http/Controllers/UserController.php',
                    'methods' => [
                        'index' => ['uses_models' => ['User']]
                    ],
                    'view_usage' => [],
                    'middleware' => [],
                    'database_interactions' => ['eloquent_queries'],
                    'dependencies' => []
                ]
            ]),
            'models' => collect([
                [
                    'class_name' => 'User',
                    'file' => '/test/app/Models/User.php'
                ]
            ]),
            'tests' => collect(),
            'views' => collect()
        ];

        $features = $this->generator->generateFeatures($analysisData);
        $feature = $features->first();

        $this->assertStringContains('# Users', $feature['content']);
        $this->assertStringContains('## Overview', $feature['content']);
        $this->assertStringContains('## Requirements', $feature['content']);
        $this->assertStringContains('## Implementation Notes', $feature['content']);
        $this->assertStringContains('## Dependencies', $feature['content']);
        $this->assertStringContains('## Acceptance Criteria', $feature['content']);
    }

    /** @test */
    public function it_extracts_code_paths_correctly()
    {
        $analysisData = [
            'routes' => collect([
                [
                    'path' => '/users',
                    'method' => 'GET',
                    'controller' => 'UserController',
                    'action' => 'index',
                    'file' => '/test/routes/web.php'
                ]
            ]),
            'controllers' => collect([
                [
                    'class_name' => 'UserController',
                    'file' => '/test/app/Http/Controllers/UserController.php',
                    'methods' => [
                        'index' => ['uses_models' => ['User']]
                    ],
                    'view_usage' => [],
                    'middleware' => [],
                    'database_interactions' => [],
                    'dependencies' => []
                ]
            ]),
            'models' => collect([
                [
                    'class_name' => 'User',
                    'file' => '/test/app/Models/User.php'
                ]
            ]),
            'tests' => collect([
                [
                    'file' => '/test/tests/Feature/UserTest.php',
                    'tested_routes' => ['/users'],
                    'tested_models' => ['User']
                ]
            ]),
            'views' => collect()
        ];

        $features = $this->generator->generateFeatures($analysisData);
        $feature = $features->first();

        $this->assertContains('/test/routes/web.php', $feature['code_paths']);
        $this->assertContains('/test/app/Http/Controllers/UserController.php', $feature['code_paths']);
        $this->assertContains('/test/app/Models/User.php', $feature['code_paths']);
        $this->assertContains('/test/tests/Feature/UserTest.php', $feature['test_paths']);
    }

    /** @test */
    public function it_skips_empty_features()
    {
        $analysisData = [
            'routes' => collect(),
            'controllers' => collect(),
            'models' => collect(),
            'tests' => collect(),
            'views' => collect()
        ];

        $features = $this->generator->generateFeatures($analysisData);

        $this->assertCount(0, $features);
    }
}