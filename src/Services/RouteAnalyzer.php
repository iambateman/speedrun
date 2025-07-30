<?php

namespace Iambateman\Speedrun\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;

class RouteAnalyzer
{
    public function analyze(array $routeFiles): Collection
    {
        $routes = collect();

        foreach ($routeFiles as $routeFile) {
            $fileRoutes = $this->parseRouteFile($routeFile);
            $routes = $routes->merge($fileRoutes);
        }

        return $routes;
    }

    private function parseRouteFile(string $filePath): Collection
    {
        if (!File::exists($filePath)) {
            return collect();
        }

        $content = File::get($filePath);
        $routes = collect();

        // Parse different route patterns
        $patterns = [
            // Route::get('path', [Controller::class, 'method'])
            '/Route::(get|post|put|patch|delete|options|any)\s*\(\s*[\'"`]([^\'"`]+)[\'"`]\s*,\s*\[([^,]+)::class\s*,\s*[\'"`]([^\'"`]+)[\'"`]\s*\]/',
            // Route::get('path', 'Controller@method')
            '/Route::(get|post|put|patch|delete|options|any)\s*\(\s*[\'"`]([^\'"`]+)[\'"`]\s*,\s*[\'"`]([^@]+)@([^\'"`]+)[\'"`]/',
            // Route::resource patterns
            '/Route::resource\s*\(\s*[\'"`]([^\'"`]+)[\'"`]\s*,\s*([^,\)]+)/',
            // Route::apiResource patterns
            '/Route::apiResource\s*\(\s*[\'"`]([^\'"`]+)[\'"`]\s*,\s*([^,\)]+)/',
        ];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $route = $this->extractRouteInfo($match, $pattern, $filePath);
                if ($route) {
                    $routes->push($route);
                }
            }
        }

        // Parse route groups
        $groupRoutes = $this->parseRouteGroups($content, $filePath);
        $routes = $routes->merge($groupRoutes);

        return $routes;
    }

    private function extractRouteInfo(array $match, string $pattern, string $filePath): ?array
    {
        $routeInfo = [
            'file' => $filePath,
            'line' => $this->findLineNumber($filePath, $match[0]),
        ];

        // Handle different route patterns
        if (str_contains($pattern, 'resource')) {
            // Resource routes
            $routeInfo['type'] = str_contains($pattern, 'apiResource') ? 'api_resource' : 'resource';
            $routeInfo['path'] = $match[1];
            $routeInfo['controller'] = $this->cleanControllerName($match[2]);
            $routeInfo['methods'] = $this->getResourceMethods($routeInfo['type']);
        } else {
            // Regular routes
            $routeInfo['method'] = strtoupper($match[1]);
            $routeInfo['path'] = $match[2];
            
            if (isset($match[4])) {
                // [Controller::class, 'method'] format
                $routeInfo['controller'] = $this->cleanControllerName($match[3]);
                $routeInfo['action'] = $match[4];
            } else {
                // 'Controller@method' format
                $routeInfo['controller'] = $match[3];
                $routeInfo['action'] = $match[4];
            }
        }

        // Extract middleware and other attributes
        $routeInfo['middleware'] = $this->extractMiddleware($filePath, $match[0]);
        $routeInfo['name'] = $this->extractRouteName($filePath, $match[0]);
        
        return $routeInfo;
    }

    private function parseRouteGroups(string $content, string $filePath): Collection
    {
        $routes = collect();
        
        // Parse Route::group patterns
        preg_match_all('/Route::group\s*\(\s*(\[.*?\])\s*,\s*function\s*\(\s*\)\s*\{(.*?)\}/s', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $groupAttributes = $this->parseGroupAttributes($match[1]);
            $groupContent = $match[2];
            
            // Parse routes within the group
            $groupRoutes = $this->parseRouteFile($filePath); // This would need to be modified to handle inline content
            
            // Apply group attributes to routes
            foreach ($groupRoutes as $route) {
                if (isset($groupAttributes['prefix'])) {
                    $route['path'] = trim($groupAttributes['prefix'], '/') . '/' . trim($route['path'], '/');
                }
                if (isset($groupAttributes['middleware'])) {
                    $route['middleware'] = array_merge($route['middleware'] ?? [], $groupAttributes['middleware']);
                }
                if (isset($groupAttributes['namespace'])) {
                    $route['namespace'] = $groupAttributes['namespace'];
                }
                
                $routes->push($route);
            }
        }
        
        return $routes;
    }

    private function parseGroupAttributes(string $attributesString): array
    {
        $attributes = [];
        
        // Simple parsing of array-like attributes
        preg_match_all('/[\'"`](\w+)[\'"`]\s*=>\s*[\'"`]([^\'"`]+)[\'"`]/', $attributesString, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $key = $match[1];
            $value = $match[2];
            
            if ($key === 'middleware') {
                $attributes[$key] = explode(',', $value);
            } else {
                $attributes[$key] = $value;
            }
        }
        
        return $attributes;
    }

    private function cleanControllerName(string $controller): string
    {
        // Remove ::class suffix
        $controller = str_replace('::class', '', $controller);
        
        // Extract just the class name from fully qualified names
        if (str_contains($controller, '\\')) {
            $parts = explode('\\', $controller);
            return end($parts);
        }
        
        return $controller;
    }

    private function getResourceMethods(string $type): array
    {
        $resourceMethods = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];
        $apiResourceMethods = ['index', 'store', 'show', 'update', 'destroy'];
        
        return $type === 'api_resource' ? $apiResourceMethods : $resourceMethods;
    }

    private function extractMiddleware(string $filePath, string $routeString): array
    {
        $middleware = [];
        
        // Look for ->middleware() calls
        if (preg_match('/->middleware\s*\(\s*[\'"`]([^\'"`]+)[\'"`]\s*\)/', $routeString, $matches)) {
            $middleware = explode(',', $matches[1]);
        } elseif (preg_match('/->middleware\s*\(\s*\[([^\]]+)\]\s*\)/', $routeString, $matches)) {
            preg_match_all('/[\'"`]([^\'"`]+)[\'"`]/', $matches[1], $middlewareMatches);
            $middleware = $middlewareMatches[1];
        }
        
        return array_map('trim', $middleware);
    }

    private function extractRouteName(string $filePath, string $routeString): ?string
    {
        if (preg_match('/->name\s*\(\s*[\'"`]([^\'"`]+)[\'"`]\s*\)/', $routeString, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    private function findLineNumber(string $filePath, string $searchString): int
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES);
        
        foreach ($lines as $lineNumber => $line) {
            if (str_contains($line, $searchString)) {
                return $lineNumber + 1; // Line numbers start at 1
            }
        }
        
        return 1;
    }
}