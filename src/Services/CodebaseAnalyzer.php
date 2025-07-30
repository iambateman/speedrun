<?php

namespace Iambateman\Speedrun\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;

class CodebaseAnalyzer
{
    public function __construct(
        private RouteAnalyzer $routeAnalyzer,
        private ControllerAnalyzer $controllerAnalyzer,
        private ModelAnalyzer $modelAnalyzer,
        private TestAnalyzer $testAnalyzer,
        private ViewAnalyzer $viewAnalyzer,
        private FeatureGenerator $featureGenerator
    ) {}

    public function analyzeCodebase(string $basePath, array $options = []): Collection
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        
        // Discover Laravel project structure
        $structure = $this->discoverProjectStructure($basePath, $options);
        
        // Analyze each component type
        $routes = $this->routeAnalyzer->analyze($structure['routes']);
        $controllers = $this->controllerAnalyzer->analyze($structure['controllers']);
        $models = $this->modelAnalyzer->analyze($structure['models']);
        $tests = $this->testAnalyzer->analyze($structure['tests']);
        $views = $this->viewAnalyzer->analyze($structure['views']);
        
        // Generate features from analysis
        return $this->featureGenerator->generateFeatures([
            'routes' => $routes,
            'controllers' => $controllers,
            'models' => $models,
            'tests' => $tests,
            'views' => $views
        ], $options);
    }

    public function analyzeSpecificFeature(string $basePath, string $featureName, array $options = []): ?array
    {
        $allFeatures = $this->analyzeCodebase($basePath, $options);
        
        return $allFeatures->firstWhere('name', $featureName);
    }

    private function discoverProjectStructure(string $basePath, array $options): array
    {
        $structure = [
            'routes' => [],
            'controllers' => [],
            'models' => [],
            'tests' => [],
            'views' => []
        ];

        // Discover routes
        $routesPaths = [
            $basePath . '/routes/web.php',
            $basePath . '/routes/api.php',
            $basePath . '/routes/auth.php',
            $basePath . '/routes/channels.php'
        ];
        
        foreach ($routesPaths as $routePath) {
            if (File::exists($routePath)) {
                $structure['routes'][] = $routePath;
            }
        }

        // Discover additional route files
        if (File::isDirectory($basePath . '/routes')) {
            $additionalRoutes = File::glob($basePath . '/routes/*.php');
            foreach ($additionalRoutes as $routeFile) {
                if (!in_array($routeFile, $structure['routes'])) {
                    $structure['routes'][] = $routeFile;
                }
            }
        }

        // Discover controllers
        if (File::isDirectory($basePath . '/app/Http/Controllers')) {
            $structure['controllers'] = $this->findFiles(
                $basePath . '/app/Http/Controllers',
                '*.php',
                $options['exclude_patterns']
            );
        }

        // Discover models
        if (File::isDirectory($basePath . '/app/Models')) {
            $structure['models'] = $this->findFiles(
                $basePath . '/app/Models',
                '*.php',
                $options['exclude_patterns']
            );
        }

        // Legacy models location
        if (File::isDirectory($basePath . '/app') && !File::isDirectory($basePath . '/app/Models')) {
            $modelFiles = File::glob($basePath . '/app/*.php');
            foreach ($modelFiles as $file) {
                if ($this->isModelFile($file)) {
                    $structure['models'][] = $file;
                }
            }
        }

        // Discover tests
        if (File::isDirectory($basePath . '/tests')) {
            $structure['tests'] = $this->findFiles(
                $basePath . '/tests',
                '*.php',
                $options['exclude_patterns']
            );
        }

        // Discover views
        if (File::isDirectory($basePath . '/resources/views')) {
            $structure['views'] = $this->findFiles(
                $basePath . '/resources/views',
                '*.blade.php',
                $options['exclude_patterns']
            );
        }

        return $structure;
    }

    private function findFiles(string $directory, string $pattern, array $excludePatterns = []): array
    {
        $files = File::allFiles($directory);
        $matchingFiles = [];

        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            
            // Check if file matches pattern
            if (!$this->matchesPattern($relativePath, $pattern)) {
                continue;
            }

            // Check if file should be excluded
            $shouldExclude = false;
            foreach ($excludePatterns as $excludePattern) {
                if ($this->matchesPattern($file->getPathname(), $excludePattern)) {
                    $shouldExclude = true;
                    break;
                }
            }

            if (!$shouldExclude) {
                $matchingFiles[] = $file->getPathname();
            }
        }

        return $matchingFiles;
    }

    private function matchesPattern(string $path, string $pattern): bool
    {
        return fnmatch($pattern, basename($path));
    }

    private function isModelFile(string $filePath): bool
    {
        $content = File::get($filePath);
        
        // Simple heuristic: check if file extends Model or uses HasFactory
        return str_contains($content, 'extends Model') || 
               str_contains($content, 'use HasFactory') ||
               str_contains($content, 'Illuminate\\Database\\Eloquent\\Model');
    }

    private function getDefaultOptions(): array
    {
        return [
            'analysis_depth' => 3,
            'include_tests' => true,
            'include_views' => true,
            'exclude_patterns' => [
                'vendor/*',
                'node_modules/*',
                'storage/*',
                '.git/*'
            ],
            'ai_enhancement' => false,
            'output_directory' => 'features/discovered'
        ];
    }
}