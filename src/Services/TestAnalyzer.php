<?php

namespace Iambateman\Speedrun\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;

class TestAnalyzer
{
    public function analyze(array $testFiles): Collection
    {
        $tests = collect();

        foreach ($testFiles as $testFile) {
            $testInfo = $this->parseTestFile($testFile);
            if ($testInfo) {
                $tests->push($testInfo);
            }
        }

        return $tests;
    }

    private function parseTestFile(string $filePath): ?array
    {
        if (!File::exists($filePath)) {
            return null;
        }

        $content = File::get($filePath);
        $className = $this->extractClassName($content, $filePath);
        
        if (!$className) {
            return null;
        }

        $testInfo = [
            'file' => $filePath,
            'class_name' => $className,
            'namespace' => $this->extractNamespace($content),
            'test_type' => $this->determineTestType($filePath, $content),
            'extends' => $this->extractParentClass($content),
            'traits' => $this->extractTraits($content),
            'test_methods' => $this->extractTestMethods($content),
            'uses_database' => $this->usesDatabase($content),
            'uses_factories' => $this->usesFactories($content),
            'uses_mocking' => $this->usesMocking($content),
            'tested_routes' => $this->extractTestedRoutes($content),
            'tested_models' => $this->extractTestedModels($content),
            'tested_controllers' => $this->extractTestedControllers($content),
            'assertions' => $this->extractAssertions($content),
            'setup_methods' => $this->extractSetupMethods($content),
            'data_providers' => $this->extractDataProviders($content),
            'dependencies' => $this->extractDependencies($content)
        ];

        return $testInfo;
    }

    private function extractClassName(string $content, string $filePath): ?string
    {
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            return $matches[1];
        }

        return basename($filePath, '.php');
    }

    private function extractNamespace(string $content): ?string
    {
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }

    private function determineTestType(string $filePath, string $content): string
    {
        // Determine based on file path
        if (str_contains($filePath, '/Feature/')) {
            return 'feature';
        } elseif (str_contains($filePath, '/Unit/')) {
            return 'unit';
        } elseif (str_contains($filePath, '/Integration/')) {
            return 'integration';
        }

        // Determine based on parent class
        if (str_contains($content, 'extends TestCase')) {
            return str_contains($content, 'use RefreshDatabase') ? 'feature' : 'unit';
        }

        return 'unknown';
    }

    private function extractParentClass(string $content): ?string
    {
        if (preg_match('/class\s+\w+\s+extends\s+(\w+)/', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    private function extractTraits(string $content): array
    {
        $traits = [];
        
        // Look for use statements within the class
        if (preg_match('/class\s+\w+.*?\{(.*?)(?:public|protected|private|\})/s', $content, $matches)) {
            $classBody = $matches[1];
            preg_match_all('/use\s+([^;]+);/', $classBody, $traitMatches);
            
            foreach ($traitMatches[1] as $trait) {
                $traits[] = trim($trait);
            }
        }
        
        return $traits;
    }

    private function extractTestMethods(string $content): array
    {
        $methods = [];
        
        // Match test methods (both test_* and /** @test */ methods)
        preg_match_all('/(?:\/\*\*[^*]*\*\s*@test[^*]*\*\/\s*)?public\s+function\s+(test_?\w+|it_\w+)\s*\([^)]*\)\s*(?::\s*[^{]+)?\s*\{(.*?)(?=public\s+function|\}$)/s', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $methodName = $match[1];
            $methodBody = $match[2] ?? '';
            
            $methods[$methodName] = [
                'name' => $methodName,
                'line_number' => $this->findLineNumber($content, $match[0]),
                'description' => $this->extractTestDescription($methodName),
                'http_methods' => $this->extractHttpMethods($methodBody),
                'routes_tested' => $this->extractRoutesInMethod($methodBody),
                'assertions_count' => $this->countAssertions($methodBody),
                'uses_authentication' => $this->usesAuthentication($methodBody),
                'creates_data' => $this->createsData($methodBody),
                'expects_exceptions' => $this->expectsExceptions($methodBody),
                'tags' => $this->extractTestTags($match[0])
            ];
        }
        
        return $methods;
    }

    private function usesDatabase(string $content): bool
    {
        return str_contains($content, 'use RefreshDatabase') || 
               str_contains($content, 'use DatabaseTransactions') ||
               str_contains($content, 'RefreshDatabase') ||
               str_contains($content, 'DatabaseTransactions');
    }

    private function usesFactories(string $content): bool
    {
        return str_contains($content, '::factory()') || 
               str_contains($content, 'factory(') ||
               str_contains($content, 'create(') ||
               str_contains($content, 'make(');
    }

    private function usesMocking(string $content): bool
    {
        return str_contains($content, 'Mock::') || 
               str_contains($content, '->shouldReceive(') ||
               str_contains($content, 'Mockery::') ||
               str_contains($content, '$this->mock(');
    }

    private function extractTestedRoutes(string $content): array
    {
        $routes = [];
        
        // Look for route testing patterns
        preg_match_all('/(?:get|post|put|patch|delete|options)\s*\(\s*[\'"`]([^\'"`]+)[\'"`]/', $content, $matches);
        foreach ($matches[1] as $route) {
            $routes[] = $route;
        }
        
        // Look for route() helper usage
        preg_match_all('/route\s*\(\s*[\'"`]([^\'"`]+)[\'"`]/', $content, $routeMatches);
        foreach ($routeMatches[1] as $route) {
            $routes[] = $route;
        }
        
        return array_unique($routes);
    }

    private function extractTestedModels(string $content): array
    {
        $models = [];
        
        // Look for Model::factory() or Model::create() patterns
        preg_match_all('/(\w+)::(?:factory|create|find|where)\s*\(/', $content, $matches);
        foreach ($matches[1] as $model) {
            if (!in_array($model, ['DB', 'Cache', 'Log', 'Auth', 'User'])) {
                $models[] = $model;
            }
        }
        
        return array_unique($models);
    }

    private function extractTestedControllers(string $content): array
    {
        $controllers = [];
        
        // Look for controller actions being tested
        preg_match_all('/action\s*\(\s*[\'"`]([^@]+)@([^\'"`]+)[\'"`]/', $content, $matches);
        for ($i = 0; $i < count($matches[1]); $i++) {
            $controllers[] = [
                'controller' => $matches[1][$i],
                'action' => $matches[2][$i]
            ];
        }
        
        return $controllers;
    }

    private function extractAssertions(string $content): array
    {
        $assertions = [];
        
        // Common assertion methods
        $assertionMethods = [
            'assertTrue', 'assertFalse', 'assertEquals', 'assertNotEquals',
            'assertSame', 'assertNotSame', 'assertNull', 'assertNotNull',
            'assertEmpty', 'assertNotEmpty', 'assertCount', 'assertContains',
            'assertStringContains', 'assertJson', 'assertJsonFragment',
            'assertStatus', 'assertRedirect', 'assertViewIs', 'assertSee',
            'assertDontSee'
        ];
        
        foreach ($assertionMethods as $assertion) {
            preg_match_all('/\$this->' . $assertion . '\s*\(([^;]+)\);/', $content, $matches);
            if (!empty($matches[0])) {
                $assertions[$assertion] = count($matches[0]);
            }
        }
        
        return $assertions;
    }

    private function extractSetupMethods(string $content): array
    {
        $methods = [];
        
        $setupMethods = ['setUp', 'tearDown', 'setUpBeforeClass', 'tearDownAfterClass'];
        
        foreach ($setupMethods as $method) {
            if (preg_match('/(?:public|protected)\s+function\s+' . $method . '\s*\(/s', $content)) {
                $methods[] = $method;
            }
        }
        
        return $methods;
    }

    private function extractDataProviders(string $content): array
    {
        $providers = [];
        
        // Look for data provider methods
        preg_match_all('/public\s+function\s+(\w+DataProvider)\s*\(\s*\)/', $content, $matches);
        foreach ($matches[1] as $provider) {
            $providers[] = $provider;
        }
        
        return $providers;
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

    private function extractTestDescription(string $methodName): string
    {
        // Convert method name to human readable description
        $description = str_replace(['test_', 'it_'], '', $methodName);
        $description = str_replace('_', ' ', $description);
        
        return ucfirst($description);
    }

    private function extractHttpMethods(string $methodBody): array
    {
        $methods = [];
        
        $httpMethods = ['get', 'post', 'put', 'patch', 'delete', 'options'];
        
        foreach ($httpMethods as $method) {
            if (str_contains($methodBody, '$this->' . $method . '(')) {
                $methods[] = strtoupper($method);
            }
        }
        
        return array_unique($methods);
    }

    private function extractRoutesInMethod(string $methodBody): array
    {
        $routes = [];
        
        preg_match_all('/(?:get|post|put|patch|delete|options)\s*\(\s*[\'"`]([^\'"`]+)[\'"`]/', $methodBody, $matches);
        foreach ($matches[1] as $route) {
            $routes[] = $route;
        }
        
        return array_unique($routes);
    }

    private function countAssertions(string $methodBody): int
    {
        return preg_match_all('/\$this->assert\w+\s*\(/', $methodBody);
    }

    private function usesAuthentication(string $methodBody): bool
    {
        return str_contains($methodBody, '$this->actingAs(') || 
               str_contains($methodBody, 'Auth::login(') ||
               str_contains($methodBody, '->be(');
    }

    private function createsData(string $methodBody): bool
    {
        return str_contains($methodBody, '::factory()') || 
               str_contains($methodBody, '::create(') ||
               str_contains($methodBody, '->create(');
    }

    private function expectsExceptions(string $methodBody): bool
    {
        return str_contains($methodBody, '$this->expectException(') || 
               str_contains($methodBody, '@expectedException');
    }

    private function extractTestTags(string $testMethod): array
    {
        $tags = [];
        
        // Look for @group tags
        if (preg_match_all('/@group\s+(\w+)/', $testMethod, $matches)) {
            $tags = array_merge($tags, $matches[1]);
        }
        
        // Look for @test tag
        if (str_contains($testMethod, '@test')) {
            $tags[] = 'test';
        }
        
        return $tags;
    }

    private function findLineNumber(string $content, string $searchString): int
    {
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNumber => $line) {
            if (str_contains($line, 'function ' . substr($searchString, 0, 20))) {
                return $lineNumber + 1;
            }
        }
        
        return 1;
    }
}