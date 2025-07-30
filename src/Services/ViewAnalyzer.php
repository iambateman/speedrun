<?php

namespace Iambateman\Speedrun\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;

class ViewAnalyzer
{
    public function analyze(array $viewFiles): Collection
    {
        $views = collect();

        foreach ($viewFiles as $viewFile) {
            $viewInfo = $this->parseViewFile($viewFile);
            if ($viewInfo) {
                $views->push($viewInfo);
            }
        }

        return $views;
    }

    private function parseViewFile(string $filePath): ?array
    {
        if (!File::exists($filePath)) {
            return null;
        }

        $content = File::get($filePath);
        $viewName = $this->extractViewName($filePath);

        $viewInfo = [
            'file' => $filePath,
            'view_name' => $viewName,
            'extends' => $this->extractExtends($content),
            'sections' => $this->extractSections($content),
            'includes' => $this->extractIncludes($content),
            'components' => $this->extractComponents($content),
            'variables' => $this->extractVariables($content),
            'loops' => $this->extractLoops($content),
            'conditionals' => $this->extractConditionals($content),
            'forms' => $this->extractForms($content),
            'assets' => $this->extractAssets($content),
            'csrf_usage' => $this->usesCsrf($content),
            'authentication_checks' => $this->extractAuthChecks($content),
            'translations' => $this->extractTranslations($content),
            'route_usage' => $this->extractRouteUsage($content),
            'javascript' => $this->extractJavaScript($content),
            'css_classes' => $this->extractCssClasses($content),
            'data_attributes' => $this->extractDataAttributes($content)
        ];

        return $viewInfo;
    }

    private function extractViewName(string $filePath): string
    {
        // Convert file path to Laravel view name (e.g., auth/login.blade.php -> auth.login)
        $relativePath = str_replace(['/resources/views/', '.blade.php'], '', $filePath);
        return str_replace('/', '.', $relativePath);
    }

    private function extractExtends(string $content): ?string
    {
        if (preg_match('/@extends\s*\(\s*[\'"`]([^\'"`]+)[\'"`]\s*\)/', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    private function extractSections(string $content): array
    {
        $sections = [];
        
        // @section directives
        preg_match_all('/@section\s*\(\s*[\'"`]([^\'"`]+)[\'"`]\s*(?:,\s*[\'"`]([^\'"`]*)[\'"`])?\s*\)/', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $sectionName = $match[1];
            $defaultContent = $match[2] ?? null;
            
            $sections[$sectionName] = [
                'name' => $sectionName,
                'default_content' => $defaultContent,
                'has_content_block' => $this->hasSectionContent($content, $sectionName)
            ];
        }
        
        // @yield directives (where sections are displayed)
        preg_match_all('/@yield\s*\(\s*[\'"`]([^\'"`]+)[\'"`](?:\s*,\s*[\'"`]([^\'"`]*)[\'"`])?\s*\)/', $content, $yieldMatches, PREG_SET_ORDER);
        
        foreach ($yieldMatches as $match) {
            $sectionName = $match[1];
            $defaultContent = $match[2] ?? null;
            
            if (!isset($sections[$sectionName])) {
                $sections[$sectionName] = [
                    'name' => $sectionName,
                    'is_yield' => true,
                    'default_content' => $defaultContent
                ];
            }
        }
        
        return $sections;
    }

    private function extractIncludes(string $content): array
    {
        $includes = [];
        
        // @include directives
        preg_match_all('/@include\s*\(\s*[\'"`]([^\'"`]+)[\'"`](?:\s*,\s*([^)]+))?\s*\)/', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $includes[] = [
                'view' => $match[1],
                'data' => isset($match[2]) ? $this->parseDataArray($match[2]) : null
            ];
        }
        
        return $includes;
    }

    private function extractComponents(string $content): array
    {
        $components = [];
        
        // Blade components (x-component-name)
        preg_match_all('/<x-([a-zA-Z0-9\-\.]+)(?:\s+([^>]*))?\s*\/?>/', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $componentName = $match[1];
            $attributes = isset($match[2]) ? $this->parseAttributes($match[2]) : [];
            
            $components[] = [
                'name' => $componentName,
                'attributes' => $attributes,
                'is_self_closing' => str_ends_with($match[0], '/>')
            ];
        }
        
        return $components;
    }

    private function extractVariables(string $content): array
    {
        $variables = [];
        
        // Laravel Blade variables ({{ $variable }})
        preg_match_all('/\{\{\s*\$(\w+)(?:->(\w+)|\[([^\]]+)\])?\s*\}\}/', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $variableName = $match[1];
            $property = $match[2] ?? null;
            $arrayKey = $match[3] ?? null;
            
            if (!in_array($variableName, array_column($variables, 'name'))) {
                $variables[] = [
                    'name' => $variableName,
                    'properties' => $property ? [$property] : [],
                    'array_keys' => $arrayKey ? [$arrayKey] : []
                ];
            } else {
                // Add to existing variable
                $index = array_search($variableName, array_column($variables, 'name'));
                if ($property && !in_array($property, $variables[$index]['properties'])) {
                    $variables[$index]['properties'][] = $property;
                }
                if ($arrayKey && !in_array($arrayKey, $variables[$index]['array_keys'])) {
                    $variables[$index]['array_keys'][] = $arrayKey;
                }
            }
        }
        
        return $variables;
    }

    private function extractLoops(string $content): array
    {
        $loops = [];
        
        // @foreach loops
        preg_match_all('/@foreach\s*\(\s*\$(\w+)\s+as\s+(?:\$(\w+)\s*=>\s*)?\$(\w+)\s*\)/', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $loops[] = [
                'type' => 'foreach',
                'collection' => $match[1],
                'key_variable' => $match[2] ?? null,
                'item_variable' => $match[3]
            ];
        }
        
        // @for loops
        preg_match_all('/@for\s*\(\s*([^)]+)\s*\)/', $content, $forMatches);
        foreach ($forMatches[1] as $forCondition) {
            $loops[] = [
                'type' => 'for',
                'condition' => $forCondition
            ];
        }
        
        // @while loops
        preg_match_all('/@while\s*\(\s*([^)]+)\s*\)/', $content, $whileMatches);
        foreach ($whileMatches[1] as $whileCondition) {
            $loops[] = [
                'type' => 'while',
                'condition' => $whileCondition
            ];
        }
        
        return $loops;
    }

    private function extractConditionals(string $content): array
    {
        $conditionals = [];
        
        // @if statements
        preg_match_all('/@if\s*\(\s*([^)]+)\s*\)/', $content, $matches);
        foreach ($matches[1] as $condition) {
            $conditionals[] = [
                'type' => 'if',
                'condition' => $condition
            ];
        }
        
        // @unless statements
        preg_match_all('/@unless\s*\(\s*([^)]+)\s*\)/', $content, $unlessMatches);
        foreach ($unlessMatches[1] as $condition) {
            $conditionals[] = [
                'type' => 'unless',
                'condition' => $condition
            ];
        }
        
        // @isset and @empty
        preg_match_all('/@(isset|empty)\s*\(\s*([^)]+)\s*\)/', $content, $checkMatches, PREG_SET_ORDER);
        foreach ($checkMatches as $match) {
            $conditionals[] = [
                'type' => $match[1],
                'variable' => $match[2]
            ];
        }
        
        return $conditionals;
    }

    private function extractForms(string $content): array
    {
        $forms = [];
        
        // Form opening tags
        preg_match_all('/<form\s+([^>]*)>/', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $attributes = $this->parseAttributes($match[1]);
            
            $forms[] = [
                'method' => $attributes['method'] ?? 'GET',
                'action' => $attributes['action'] ?? null,
                'attributes' => $attributes,
                'has_csrf' => $this->formHasCsrf($content, $match[0])
            ];
        }
        
        return $forms;
    }

    private function extractAssets(string $content): array
    {
        $assets = [];
        
        // @vite directive
        if (preg_match('/@vite\s*\(\s*([^)]+)\s*\)/', $content, $matches)) {
            $assets['vite'] = $this->parseViteAssets($matches[1]);
        }
        
        // Legacy asset() calls
        preg_match_all('/asset\s*\(\s*[\'"`]([^\'"`]+)[\'"`]\s*\)/', $content, $assetMatches);
        $assets['static'] = $assetMatches[1];
        
        // CSS and JS links
        preg_match_all('/<link[^>]*href\s*=\s*[\'"`]([^\'"`]+)[\'"`]/', $content, $cssMatches);
        $assets['css'] = $cssMatches[1];
        
        preg_match_all('/<script[^>]*src\s*=\s*[\'"`]([^\'"`]+)[\'"`]/', $content, $jsMatches);
        $assets['js'] = $jsMatches[1];
        
        return $assets;
    }

    private function usesCsrf(string $content): bool
    {
        return str_contains($content, '@csrf') || 
               str_contains($content, 'csrf_token()') ||
               str_contains($content, '_token');
    }

    private function extractAuthChecks(string $content): array
    {
        $authChecks = [];
        
        // @auth and @guest directives
        if (str_contains($content, '@auth')) {
            $authChecks[] = 'auth';
        }
        if (str_contains($content, '@guest')) {
            $authChecks[] = 'guest';
        }
        
        // @can directives
        preg_match_all('/@can\s*\(\s*[\'"`]([^\'"`]+)[\'"`]/', $content, $canMatches);
        foreach ($canMatches[1] as $permission) {
            $authChecks[] = "can:{$permission}";
        }
        
        return $authChecks;
    }

    private function extractTranslations(string $content): array
    {
        $translations = [];
        
        // @lang directive
        preg_match_all('/@lang\s*\(\s*[\'"`]([^\'"`]+)[\'"`]\s*\)/', $content, $langMatches);
        $translations = array_merge($translations, $langMatches[1]);
        
        // __() helper
        preg_match_all('/__\s*\(\s*[\'"`]([^\'"`]+)[\'"`]\s*\)/', $content, $transMatches);
        $translations = array_merge($translations, $transMatches[1]);
        
        return array_unique($translations);
    }

    private function extractRouteUsage(string $content): array
    {
        $routes = [];
        
        // route() helper
        preg_match_all('/route\s*\(\s*[\'"`]([^\'"`]+)[\'"`]/', $content, $matches);
        foreach ($matches[1] as $route) {
            $routes[] = $route;
        }
        
        return array_unique($routes);
    }

    private function extractJavaScript(string $content): array
    {
        $js = [];
        
        // Inline JavaScript
        preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $content, $matches);
        foreach ($matches[1] as $script) {
            if (trim($script)) {
                $js[] = [
                    'type' => 'inline',
                    'content' => trim($script)
                ];
            }
        }
        
        // @push('scripts') blocks
        if (str_contains($content, "@push('scripts')") || str_contains($content, '@push("scripts")')) {
            $js[] = [
                'type' => 'pushed',
                'stack' => 'scripts'
            ];
        }
        
        return $js;
    }

    private function extractCssClasses(string $content): array
    {
        $classes = [];
        
        // Extract class attributes
        preg_match_all('/class\s*=\s*[\'"`]([^\'"`]+)[\'"`]/', $content, $matches);
        
        foreach ($matches[1] as $classString) {
            $classNames = explode(' ', $classString);
            foreach ($classNames as $className) {
                $className = trim($className);
                if ($className && !in_array($className, $classes)) {
                    $classes[] = $className;
                }
            }
        }
        
        return $classes;
    }

    private function extractDataAttributes(string $content): array
    {
        $dataAttributes = [];
        
        preg_match_all('/data-([a-zA-Z0-9\-]+)\s*=\s*[\'"`]([^\'"`]*)[\'"`]/', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $dataAttributes[$match[1]] = $match[2];
        }
        
        return $dataAttributes;
    }

    private function hasSectionContent(string $content, string $sectionName): bool
    {
        $pattern = '/@section\s*\(\s*[\'"`]' . preg_quote($sectionName) . '[\'"`]\s*\).*?@endsection/s';
        return preg_match($pattern, $content) === 1;
    }

    private function parseDataArray(string $dataString): array
    {
        // Simple parsing of array-like data
        $data = [];
        preg_match_all('/[\'"`](\w+)[\'"`]\s*=>\s*[\'"`]?([^,\]]+)[\'"`]?/', $dataString, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $data[$match[1]] = trim($match[2], '\'"');
        }
        
        return $data;
    }

    private function parseAttributes(string $attributeString): array
    {
        $attributes = [];
        
        preg_match_all('/(\w+)\s*=\s*[\'"`]([^\'"`]*)[\'"`]/', $attributeString, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $attributes[$match[1]] = $match[2];
        }
        
        return $attributes;
    }

    private function parseViteAssets(string $viteString): array
    {
        $assets = [];
        
        // Parse Vite asset array
        preg_match_all('/[\'"`]([^\'"`]+)[\'"`]/', $viteString, $matches);
        
        return $matches[1];
    }

    private function formHasCsrf(string $content, string $formTag): bool
    {
        // Look for CSRF token within the form
        $formEndPos = strpos($content, '</form>', strpos($content, $formTag));
        if ($formEndPos === false) {
            return false;
        }
        
        $formContent = substr($content, strpos($content, $formTag), $formEndPos - strpos($content, $formTag));
        
        return $this->usesCsrf($formContent);
    }
}