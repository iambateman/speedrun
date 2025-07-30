<?php

namespace Iambateman\Speedrun\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;

class ControllerAnalyzer
{
    public function analyze(array $controllerFiles): Collection
    {
        $controllers = collect();

        foreach ($controllerFiles as $controllerFile) {
            $controllerInfo = $this->parseControllerFile($controllerFile);
            if ($controllerInfo) {
                $controllers->push($controllerInfo);
            }
        }

        return $controllers;
    }

    private function parseControllerFile(string $filePath): ?array
    {
        if (!File::exists($filePath)) {
            return null;
        }

        $content = File::get($filePath);
        $className = $this->extractClassName($content, $filePath);
        
        if (!$className) {
            return null;
        }

        $controllerInfo = [
            'file' => $filePath,
            'class_name' => $className,
            'namespace' => $this->extractNamespace($content),
            'methods' => $this->extractMethods($content),
            'middleware' => $this->extractControllerMiddleware($content),
            'uses_traits' => $this->extractTraits($content),
            'extends' => $this->extractParentClass($content),
            'dependencies' => $this->extractDependencies($content),
            'validation_rules' => $this->extractValidationRules($content),
            'database_interactions' => $this->extractDatabaseInteractions($content),
            'view_usage' => $this->extractViewUsage($content),
            'api_resources' => $this->extractApiResources($content)
        ];

        return $controllerInfo;
    }

    private function extractClassName(string $content, string $filePath): ?string
    {
        // Try to extract class name from class declaration
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            return $matches[1];
        }

        // Fallback to filename
        $filename = basename($filePath, '.php');
        return $filename;
    }

    private function extractNamespace(string $content): ?string
    {
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }

    private function extractMethods(string $content): array
    {
        $methods = [];
        
        // Match public methods
        preg_match_all('/public\s+function\s+(\w+)\s*\([^)]*\)(?:\s*:\s*[^{]+)?\s*\{(.*?)\n\s*\}/s', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $methodName = $match[1];
            $methodBody = $match[2];
            
            $methods[$methodName] = [
                'name' => $methodName,
                'parameters' => $this->extractMethodParameters($match[0]),
                'return_type' => $this->extractReturnType($match[0]),
                'http_methods' => $this->inferHttpMethods($methodName),
                'validates_request' => $this->hasValidation($methodBody),
                'uses_models' => $this->extractModelUsage($methodBody),
                'returns_view' => $this->returnsView($methodBody),
                'returns_json' => $this->returnsJson($methodBody),
                'redirects' => $this->hasRedirects($methodBody),
                'middleware' => $this->extractMethodMiddleware($match[0]),
                'line_number' => $this->findLineNumber($content, $match[0])
            ];
        }
        
        return $methods;
    }

    private function extractMethodParameters(string $methodSignature): array
    {
        $parameters = [];
        
        if (preg_match('/\(([^)]+)\)/', $methodSignature, $matches)) {
            $paramString = $matches[1];
            $params = explode(',', $paramString);
            
            foreach ($params as $param) {
                $param = trim($param);
                if (empty($param)) continue;
                
                // Extract type and name
                if (preg_match('/(?:(\w+)\s+)?(?:\$)?(\w+)/', $param, $paramMatches)) {
                    $parameters[] = [
                        'type' => $paramMatches[1] ?? null,
                        'name' => $paramMatches[2] ?? $param
                    ];
                }
            }
        }
        
        return $parameters;
    }

    private function extractReturnType(string $methodSignature): ?string
    {
        if (preg_match('/:\s*([^{]+)/', $methodSignature, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }

    private function inferHttpMethods(string $methodName): array
    {
        $methodMap = [
            'index' => ['GET'],
            'create' => ['GET'],
            'store' => ['POST'],
            'show' => ['GET'],
            'edit' => ['GET'],
            'update' => ['PUT', 'PATCH'],
            'destroy' => ['DELETE'],
        ];
        
        return $methodMap[$methodName] ?? ['GET', 'POST'];
    }

    private function hasValidation(string $methodBody): bool
    {
        return str_contains($methodBody, '->validate(') || 
               str_contains($methodBody, 'request()->validate(') ||
               str_contains($methodBody, 'Validator::make');
    }

    private function extractModelUsage(string $methodBody): array
    {
        $models = [];
        
        // Look for Model::method() patterns
        preg_match_all('/(\w+)::(find|create|where|all|first|get|with)\s*\(/', $methodBody, $matches);
        foreach ($matches[1] as $model) {
            if (!in_array($model, ['DB', 'Cache', 'Log', 'Auth'])) {
                $models[] = $model;
            }
        }
        
        // Look for $model->method() patterns
        preg_match_all('/\$(\w+)->(save|delete|update|create)\s*\(/', $methodBody, $matches);
        foreach ($matches[1] as $variable) {
            if (!in_array($variable, ['request', 'user', 'this'])) {
                $models[] = $variable;
            }
        }
        
        return array_unique($models);
    }

    private function returnsView(string $methodBody): bool
    {
        return str_contains($methodBody, 'return view(') || 
               str_contains($methodBody, 'view(');
    }

    private function returnsJson(string $methodBody): bool
    {
        return str_contains($methodBody, 'return response()->json(') || 
               str_contains($methodBody, '->json(') ||
               str_contains($methodBody, 'return new ') && str_contains($methodBody, 'Resource');
    }

    private function hasRedirects(string $methodBody): bool
    {
        return str_contains($methodBody, 'return redirect(') || 
               str_contains($methodBody, '->redirect(');
    }

    private function extractControllerMiddleware(string $content): array
    {
        $middleware = [];
        
        // Look for $this->middleware() in constructor
        if (preg_match('/__construct.*?\{(.*?)\}/s', $content, $matches)) {
            $constructorBody = $matches[1];
            preg_match_all('/\$this->middleware\s*\(\s*[\'"`]([^\'"`]+)[\'"`]/', $constructorBody, $middlewareMatches);
            $middleware = array_merge($middleware, $middlewareMatches[1]);
        }
        
        return $middleware;
    }

    private function extractMethodMiddleware(string $methodSignature): array
    {
        // This would require more complex parsing to find method-specific middleware
        // For now, return empty array
        return [];
    }

    private function extractTraits(string $content): array
    {
        $traits = [];
        
        preg_match_all('/use\s+([^;]+);/', $content, $matches);
        foreach ($matches[1] as $use) {
            // Check if it's a trait (simple heuristic)
            if (str_contains($use, 'Trait') || str_contains($use, 'Authorizable') || str_contains($use, 'Dispatchable')) {
                $traits[] = trim($use);
            }
        }
        
        return $traits;
    }

    private function extractParentClass(string $content): ?string
    {
        if (preg_match('/class\s+\w+\s+extends\s+(\w+)/', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    private function extractDependencies(string $content): array
    {
        $dependencies = [];
        
        // Extract use statements
        preg_match_all('/use\s+([^;]+);/', $content, $matches);
        foreach ($matches[1] as $use) {
            $dependencies[] = trim($use);
        }
        
        return $dependencies;
    }

    private function extractValidationRules(string $content): array
    {
        $rules = [];
        
        // Look for validation rules arrays
        preg_match_all('/\$rules\s*=\s*\[(.*?)\]/s', $content, $matches);
        foreach ($matches[1] as $rulesArray) {
            // Simple extraction of rule strings
            preg_match_all('/[\'"`]([^\'"`]+)[\'"`]\s*=>\s*[\'"`]([^\'"`]+)[\'"`]/', $rulesArray, $ruleMatches, PREG_SET_ORDER);
            foreach ($ruleMatches as $rule) {
                $rules[$rule[1]] = $rule[2];
            }
        }
        
        return $rules;
    }

    private function extractDatabaseInteractions(string $content): array
    {
        $interactions = [];
        
        // Look for DB facade usage
        if (str_contains($content, 'DB::')) {
            $interactions[] = 'raw_queries';
        }
        
        // Look for Eloquent usage
        if (str_contains($content, '::find') || str_contains($content, '::where')) {
            $interactions[] = 'eloquent_queries';
        }
        
        // Look for transactions
        if (str_contains($content, 'DB::transaction')) {
            $interactions[] = 'transactions';
        }
        
        return array_unique($interactions);
    }

    private function extractViewUsage(string $content): array
    {
        $views = [];
        
        preg_match_all('/view\s*\(\s*[\'"`]([^\'"`]+)[\'"`]/', $content, $matches);
        foreach ($matches[1] as $view) {
            $views[] = $view;
        }
        
        return array_unique($views);
    }

    private function extractApiResources(string $content): array
    {
        $resources = [];
        
        preg_match_all('/new\s+(\w+Resource)\s*\(/', $content, $matches);
        foreach ($matches[1] as $resource) {
            $resources[] = $resource;
        }
        
        return array_unique($resources);
    }

    private function findLineNumber(string $content, string $searchString): int
    {
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNumber => $line) {
            if (str_contains($line, 'function ' . $searchString)) {
                return $lineNumber + 1;
            }
        }
        
        return 1;
    }
}