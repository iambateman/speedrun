<?php

namespace Iambateman\Speedrun\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FeatureGenerator
{
    public function generateFeatures(array $analysisData, array $options = []): Collection
    {
        $features = collect();
        
        // Group related components into logical features
        $featureGroups = $this->groupComponentsIntoFeatures($analysisData);
        
        foreach ($featureGroups as $featureName => $components) {
            $feature = $this->generateFeature($featureName, $components, $options);
            if ($feature) {
                $features->push($feature);
            }
        }
        
        return $features;
    }

    private function groupComponentsIntoFeatures(array $analysisData): array
    {
        $features = [];
        
        // Start with routes as the primary grouping mechanism
        foreach ($analysisData['routes'] as $route) {
            $featureName = $this->determineFeatureFromRoute($route);
            
            if (!isset($features[$featureName])) {
                $features[$featureName] = [
                    'routes' => [],
                    'controllers' => [],
                    'models' => [],
                    'views' => [],
                    'tests' => []
                ];
            }
            
            $features[$featureName]['routes'][] = $route;
            
            // Find related controller
            if (isset($route['controller'])) {
                $controller = $this->findControllerByName($analysisData['controllers'], $route['controller']);
                if ($controller && !$this->isControllerInFeature($features[$featureName]['controllers'], $controller)) {
                    $features[$featureName]['controllers'][] = $controller;
                }
            }
        }
        
        // Add models based on controller usage
        foreach ($features as $featureName => &$feature) {
            foreach ($feature['controllers'] as $controller) {
                foreach ($controller['methods'] as $method) {
                    foreach ($method['uses_models'] as $modelName) {
                        $model = $this->findModelByName($analysisData['models'], $modelName);
                        if ($model && !$this->isModelInFeature($feature['models'], $model)) {
                            $feature['models'][] = $model;
                        }
                    }
                }
            }
        }
        
        // Add views based on controller usage
        foreach ($features as $featureName => &$feature) {
            foreach ($feature['controllers'] as $controller) {
                foreach ($controller['view_usage'] as $viewName) {
                    $view = $this->findViewByName($analysisData['views'], $viewName);
                    if ($view && !$this->isViewInFeature($feature['views'], $view)) {
                        $feature['views'][] = $view;
                    }
                }
            }
        }
        
        // Add related tests
        foreach ($features as $featureName => &$feature) {
            foreach ($analysisData['tests'] as $test) {
                if ($this->testRelatedToFeature($test, $feature)) {
                    $feature['tests'][] = $test;
                }
            }
        }
        
        // Handle orphaned components
        $features = $this->handleOrphanedComponents($features, $analysisData);
        
        return $features;
    }

    private function determineFeatureFromRoute(array $route): string
    {
        // Extract feature name from route path or controller
        $path = $route['path'] ?? '';
        $controller = $route['controller'] ?? '';
        
        // Remove leading slash and extract first segment
        $pathSegments = explode('/', trim($path, '/'));
        $firstSegment = $pathSegments[0] ?? '';
        
        // Skip common prefixes
        if (in_array($firstSegment, ['api', 'admin', 'dashboard'])) {
            $firstSegment = $pathSegments[1] ?? $firstSegment;
        }
        
        // Use controller name if path doesn't provide good feature name
        if (empty($firstSegment) || in_array($firstSegment, ['', 'home', 'index'])) {
            $controllerName = str_replace('Controller', '', $controller);
            $firstSegment = Str::kebab(Str::plural($controllerName));
        }
        
        // Handle resource routes
        if (isset($route['type']) && str_contains($route['type'], 'resource')) {
            $resourceName = $route['path'];
            return Str::singular($resourceName) . '-management';
        }
        
        return $firstSegment ?: 'general';
    }

    private function generateFeature(string $featureName, array $components, array $options): ?array
    {
        if (empty($components['routes']) && empty($components['controllers']) && empty($components['models'])) {
            return null; // Skip empty features
        }
        
        $title = $this->generateFeatureTitle($featureName);
        $description = $this->generateFeatureDescription($featureName, $components);
        $requirements = $this->generateRequirements($components);
        $implementationNotes = $this->generateImplementationNotes($components);
        $dependencies = $this->generateDependencies($components);
        $acceptanceCriteria = $this->generateAcceptanceCriteria($components);
        
        $content = $this->buildFeatureContent(
            $title,
            $description,
            $requirements,
            $implementationNotes,
            $dependencies,
            $acceptanceCriteria
        );
        
        return [
            'name' => $featureName,
            'title' => $title,
            'content' => $content,
            'code_paths' => $this->extractCodePaths($components),
            'test_paths' => $this->extractTestPaths($components),
            'routes' => $components['routes'],
            'controllers' => $components['controllers'],
            'models' => $components['models'],
            'views' => $components['views'],
            'tests' => $components['tests']
        ];
    }

    private function generateFeatureTitle(string $featureName): string
    {
        return Str::title(str_replace(['-', '_'], ' ', $featureName));
    }

    private function generateFeatureDescription(string $featureName, array $components): string
    {
        $routeCount = count($components['routes']);
        $controllerCount = count($components['controllers']);
        $modelCount = count($components['models']);
        
        $description = "Comprehensive {$featureName} functionality ";
        
        if ($routeCount > 0) {
            $description .= "with {$routeCount} route" . ($routeCount > 1 ? 's' : '') . ', ';
        }
        
        if ($controllerCount > 0) {
            $description .= "{$controllerCount} controller" . ($controllerCount > 1 ? 's' : '') . ', ';
        }
        
        if ($modelCount > 0) {
            $description .= "and {$modelCount} model" . ($modelCount > 1 ? 's' : '') . '.';
        }
        
        return rtrim($description, ', ') . '.';
    }

    private function generateRequirements(array $components): array
    {
        $requirements = [];
        
        // Functional requirements from routes
        foreach ($components['routes'] as $route) {
            $method = $route['method'] ?? 'GET';
            $path = $route['path'] ?? '';
            $action = $route['action'] ?? '';
            
            if ($action) {
                $requirement = $this->routeToRequirement($method, $path, $action);
                if ($requirement) {
                    $requirements[] = $requirement;
                }
            }
        }
        
        // Authentication requirements
        if ($this->hasAuthenticationFeatures($components)) {
            $requirements[] = 'System must authenticate and authorize users appropriately';
        }
        
        // Database requirements
        if ($this->hasDatabaseInteractions($components)) {
            $requirements[] = 'System must persist data reliably to the database';
        }
        
        // Validation requirements
        if ($this->hasValidationFeatures($components)) {
            $requirements[] = 'System must validate user input according to business rules';
        }
        
        // Non-functional requirements
        $requirements[] = 'Response time should be under 500ms for standard operations';
        $requirements[] = 'System should handle concurrent users appropriately';
        
        return array_unique($requirements);
    }

    private function generateImplementationNotes(array $components): string
    {
        $notes = [];
        
        // Controller patterns
        $controllerNames = array_map(fn($c) => $c['class_name'], $components['controllers']);
        if (!empty($controllerNames)) {
            $notes[] = 'Uses ' . implode(', ', $controllerNames) . ' controller' . (count($controllerNames) > 1 ? 's' : '');
        }
        
        // Model patterns
        $modelNames = array_map(fn($m) => $m['class_name'], $components['models']);
        if (!empty($modelNames)) {
            $notes[] = 'Interacts with ' . implode(', ', $modelNames) . ' model' . (count($modelNames) > 1 ? 's' : '');
        }
        
        // Authentication patterns
        if ($this->hasAuthenticationFeatures($components)) {
            $notes[] = 'Implements Laravel authentication and authorization';
        }
        
        // API patterns
        if ($this->hasApiFeatures($components)) {
            $notes[] = 'Provides RESTful API endpoints with appropriate response formats';
        }
        
        // Form handling
        if ($this->hasFormHandling($components)) {
            $notes[] = 'Handles form submissions with CSRF protection and validation';
        }
        
        return implode('. ', $notes) . '.';
    }

    private function generateDependencies(array $components): array
    {
        $dependencies = ['Laravel Framework'];
        
        // Extract unique dependencies from controllers
        foreach ($components['controllers'] as $controller) {
            foreach ($controller['dependencies'] as $dependency) {
                if (!in_array($dependency, $dependencies) && $this->isExternalDependency($dependency)) {
                    $dependencies[] = $this->cleanDependencyName($dependency);
                }
            }
        }
        
        // Common Laravel dependencies based on features
        if ($this->hasAuthenticationFeatures($components)) {
            $dependencies[] = 'Laravel Sanctum or Passport for API authentication';
        }
        
        if ($this->hasValidationFeatures($components)) {
            $dependencies[] = 'Laravel Validation';
        }
        
        return array_unique($dependencies);
    }

    private function generateAcceptanceCriteria(array $components): array
    {
        $criteria = [];
        
        // Generate criteria from routes
        foreach ($components['routes'] as $route) {
            $criterion = $this->routeToAcceptanceCriterion($route);
            if ($criterion) {
                $criteria[] = $criterion;
            }
        }
        
        // Generate criteria from test scenarios
        foreach ($components['tests'] as $test) {
            foreach ($test['test_methods'] as $method) {
                $criterion = $this->testToAcceptanceCriterion($method);
                if ($criterion) {
                    $criteria[] = $criterion;
                }
            }
        }
        
        // Add common criteria
        if ($this->hasAuthenticationFeatures($components)) {
            $criteria[] = 'Unauthorized users cannot access protected resources';
        }
        
        if ($this->hasValidationFeatures($components)) {
            $criteria[] = 'Invalid input shows appropriate error messages';
        }
        
        return array_unique($criteria);
    }

    private function buildFeatureContent(
        string $title,
        string $description,
        array $requirements,
        string $implementationNotes,
        array $dependencies,
        array $acceptanceCriteria
    ): string {
        $content = "# {$title}\n\n";
        $content .= "## Overview\n{$description}\n\n";
        
        $content .= "## Requirements\n";
        foreach ($requirements as $index => $requirement) {
            $type = $index < 3 ? 'Functional' : 'Non-functional';
            $content .= "- {$type} requirement: {$requirement}\n";
        }
        $content .= "\n";
        
        $content .= "## Implementation Notes\n{$implementationNotes}\n\n";
        
        $content .= "## Dependencies\n";
        foreach ($dependencies as $dependency) {
            $content .= "- {$dependency}\n";
        }
        $content .= "\n";
        
        $content .= "## Acceptance Criteria\n";
        foreach ($acceptanceCriteria as $criterion) {
            $content .= "- [ ] {$criterion}\n";
        }
        
        return $content;
    }

    // Helper methods for finding components
    private function findControllerByName(Collection $controllers, string $name): ?array
    {
        return $controllers->first(fn($c) => $c['class_name'] === $name);
    }

    private function findModelByName(Collection $models, string $name): ?array
    {
        return $models->first(fn($m) => $m['class_name'] === $name);
    }

    private function findViewByName(Collection $views, string $name): ?array
    {
        return $views->first(fn($v) => $v['view_name'] === $name);
    }

    // Helper methods for checking feature relationships
    private function isControllerInFeature(array $controllers, array $controller): bool
    {
        return collect($controllers)->contains('class_name', $controller['class_name']);
    }

    private function isModelInFeature(array $models, array $model): bool
    {
        return collect($models)->contains('class_name', $model['class_name']);
    }

    private function isViewInFeature(array $views, array $view): bool
    {
        return collect($views)->contains('view_name', $view['view_name']);
    }

    private function testRelatedToFeature(array $test, array $feature): bool
    {
        // Check if test routes match feature routes
        foreach ($test['tested_routes'] as $testedRoute) {
            foreach ($feature['routes'] as $route) {
                if (str_contains($route['path'], $testedRoute) || str_contains($testedRoute, $route['path'])) {
                    return true;
                }
            }
        }
        
        // Check if test models match feature models
        foreach ($test['tested_models'] as $testedModel) {
            foreach ($feature['models'] as $model) {
                if ($model['class_name'] === $testedModel) {
                    return true;
                }
            }
        }
        
        return false;
    }

    private function handleOrphanedComponents(array $features, array $analysisData): array
    {
        // This would handle components not assigned to any feature
        // For now, create a 'general' feature for orphaned components
        return $features;
    }

    // Helper methods for extracting paths
    private function extractCodePaths(array $components): array
    {
        $paths = [];
        
        foreach ($components['routes'] as $route) {
            $paths[] = $route['file'];
        }
        
        foreach ($components['controllers'] as $controller) {
            $paths[] = $controller['file'];
        }
        
        foreach ($components['models'] as $model) {
            $paths[] = $model['file'];
        }
        
        foreach ($components['views'] as $view) {
            $paths[] = $view['file'];
        }
        
        return array_unique($paths);
    }

    private function extractTestPaths(array $components): array
    {
        return array_unique(array_map(fn($test) => $test['file'], $components['tests']));
    }

    // Helper methods for feature detection
    private function hasAuthenticationFeatures(array $components): bool
    {
        foreach ($components['controllers'] as $controller) {
            if (in_array('auth', $controller['middleware']) || str_contains($controller['class_name'], 'Auth')) {
                return true;
            }
        }
        return false;
    }

    private function hasDatabaseInteractions(array $components): bool
    {
        return !empty($components['models']) || 
               collect($components['controllers'])->some(fn($c) => !empty($c['database_interactions']));
    }

    private function hasValidationFeatures(array $components): bool
    {
        foreach ($components['controllers'] as $controller) {
            foreach ($controller['methods'] as $method) {
                if ($method['validates_request']) {
                    return true;
                }
            }
        }
        return false;
    }

    private function hasApiFeatures(array $components): bool
    {
        foreach ($components['routes'] as $route) {
            if (str_contains($route['file'], 'api.php') || str_contains($route['path'], '/api/')) {
                return true;
            }
        }
        return false;
    }

    private function hasFormHandling(array $components): bool
    {
        foreach ($components['views'] as $view) {
            if (!empty($view['forms'])) {
                return true;
            }
        }
        return false;
    }

    // Helper methods for generating content
    private function routeToRequirement(string $method, string $path, string $action): ?string
    {
        $actionMap = [
            'index' => 'Users can view a list of items',
            'show' => 'Users can view individual item details',
            'create' => 'Users can access the creation form',
            'store' => 'Users can create new items',
            'edit' => 'Users can access the edit form',
            'update' => 'Users can update existing items',
            'destroy' => 'Users can delete items'
        ];
        
        return $actionMap[$action] ?? "Users can {$action} via {$method} {$path}";
    }

    private function routeToAcceptanceCriterion(array $route): ?string
    {
        $method = $route['method'] ?? 'GET';
        $path = $route['path'] ?? '';
        $action = $route['action'] ?? '';
        
        if ($action === 'index') {
            return "GET {$path} returns a list of items with proper pagination";
        } elseif ($action === 'store') {
            return "POST {$path} creates a new item with valid data";
        } elseif ($action === 'update') {
            return "PUT/PATCH {$path} updates existing item with valid data";
        } elseif ($action === 'destroy') {
            return "DELETE {$path} removes item and returns appropriate response";
        }
        
        return "{$method} {$path} responds with appropriate status code";
    }

    private function testToAcceptanceCriterion(array $method): ?string
    {
        $description = $method['description'];
        return ucfirst($description);
    }

    private function isExternalDependency(string $dependency): bool
    {
        $internalPatterns = ['App\\', 'Illuminate\\', 'Laravel\\'];
        
        foreach ($internalPatterns as $pattern) {
            if (str_starts_with($dependency, $pattern)) {
                return false;
            }
        }
        
        return true;
    }

    private function cleanDependencyName(string $dependency): string
    {
        // Extract package name from namespace
        $parts = explode('\\', $dependency);
        return $parts[0] ?? $dependency;
    }
}