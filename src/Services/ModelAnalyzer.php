<?php

namespace Iambateman\Speedrun\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;

class ModelAnalyzer
{
    public function analyze(array $modelFiles): Collection
    {
        $models = collect();

        foreach ($modelFiles as $modelFile) {
            $modelInfo = $this->parseModelFile($modelFile);
            if ($modelInfo) {
                $models->push($modelInfo);
            }
        }

        return $models;
    }

    private function parseModelFile(string $filePath): ?array
    {
        if (!File::exists($filePath)) {
            return null;
        }

        $content = File::get($filePath);
        $className = $this->extractClassName($content, $filePath);
        
        if (!$className) {
            return null;
        }

        $modelInfo = [
            'file' => $filePath,
            'class_name' => $className,
            'namespace' => $this->extractNamespace($content),
            'table_name' => $this->extractTableName($content, $className),
            'fillable' => $this->extractFillable($content),
            'guarded' => $this->extractGuarded($content),
            'hidden' => $this->extractHidden($content),
            'casts' => $this->extractCasts($content),
            'dates' => $this->extractDates($content),
            'relationships' => $this->extractRelationships($content),
            'scopes' => $this->extractScopes($content),
            'mutators' => $this->extractMutators($content),
            'accessors' => $this->extractAccessors($content),
            'traits' => $this->extractTraits($content),
            'extends' => $this->extractParentClass($content),
            'uses_soft_deletes' => $this->usesSoftDeletes($content),
            'uses_timestamps' => $this->usesTimestamps($content),
            'factories' => $this->hasFactory($content),
            'observers' => $this->extractObservers($content),
            'validation_rules' => $this->extractValidationRules($content)
        ];

        return $modelInfo;
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

    private function extractTableName(string $content, string $className): string
    {
        // Look for explicit table definition
        if (preg_match('/\$table\s*=\s*[\'"`]([^\'"`]+)[\'"`]/', $content, $matches)) {
            return $matches[1];
        }

        // Default Laravel convention: snake_case plural
        return $this->pluralize($this->camelToSnake($className));
    }

    private function extractFillable(string $content): array
    {
        if (preg_match('/\$fillable\s*=\s*\[(.*?)\]/s', $content, $matches)) {
            return $this->parseArrayValues($matches[1]);
        }
        
        return [];
    }

    private function extractGuarded(string $content): array
    {
        if (preg_match('/\$guarded\s*=\s*\[(.*?)\]/s', $content, $matches)) {
            return $this->parseArrayValues($matches[1]);
        }
        
        return [];
    }

    private function extractHidden(string $content): array
    {
        if (preg_match('/\$hidden\s*=\s*\[(.*?)\]/s', $content, $matches)) {
            return $this->parseArrayValues($matches[1]);
        }
        
        return [];
    }

    private function extractCasts(string $content): array
    {
        $casts = [];
        
        if (preg_match('/\$casts\s*=\s*\[(.*?)\]/s', $content, $matches)) {
            $castsArray = $matches[1];
            preg_match_all('/[\'"`]([^\'"`]+)[\'"`]\s*=>\s*[\'"`]([^\'"`]+)[\'"`]/', $castsArray, $castMatches, PREG_SET_ORDER);
            
            foreach ($castMatches as $cast) {
                $casts[$cast[1]] = $cast[2];
            }
        }
        
        return $casts;
    }

    private function extractDates(string $content): array
    {
        if (preg_match('/\$dates\s*=\s*\[(.*?)\]/s', $content, $matches)) {
            return $this->parseArrayValues($matches[1]);
        }
        
        return [];
    }

    private function extractRelationships(string $content): array
    {
        $relationships = [];
        
        // Look for relationship methods
        $relationshipMethods = [
            'hasOne', 'hasMany', 'belongsTo', 'belongsToMany', 
            'morphTo', 'morphOne', 'morphMany', 'morphToMany', 
            'morphedByMany', 'hasManyThrough'
        ];
        
        foreach ($relationshipMethods as $method) {
            preg_match_all('/public\s+function\s+(\w+)\s*\([^)]*\)(?:\s*:\s*[^{]+)?\s*\{[^}]*return\s+\$this->' . $method . '\s*\(([^;]+)\);/s', $content, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $relationshipName = $match[1];
                $parameters = $this->parseRelationshipParameters($match[2]);
                
                $relationships[$relationshipName] = [
                    'type' => $method,
                    'related_model' => $parameters['model'] ?? null,
                    'foreign_key' => $parameters['foreign_key'] ?? null,
                    'local_key' => $parameters['local_key'] ?? null,
                    'pivot_table' => $parameters['pivot_table'] ?? null,
                    'line_number' => $this->findLineNumber($content, $match[0])
                ];
            }
        }
        
        return $relationships;
    }

    private function extractScopes(string $content): array
    {
        $scopes = [];
        
        // Look for scope methods
        preg_match_all('/public\s+function\s+scope(\w+)\s*\([^)]*\)/', $content, $matches);
        
        foreach ($matches[1] as $scope) {
            $scopes[] = lcfirst($scope); // Convert ScopeActive to active
        }
        
        return $scopes;
    }

    private function extractMutators(string $content): array
    {
        $mutators = [];
        
        // Look for mutator methods (setXxxAttribute)
        preg_match_all('/public\s+function\s+set(\w+)Attribute\s*\([^)]*\)/', $content, $matches);
        
        foreach ($matches[1] as $attribute) {
            $mutators[] = $this->camelToSnake($attribute);
        }
        
        return $mutators;
    }

    private function extractAccessors(string $content): array
    {
        $accessors = [];
        
        // Look for accessor methods (getXxxAttribute)
        preg_match_all('/public\s+function\s+get(\w+)Attribute\s*\([^)]*\)/', $content, $matches);
        
        foreach ($matches[1] as $attribute) {
            $accessors[] = $this->camelToSnake($attribute);
        }
        
        return $accessors;
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

    private function extractParentClass(string $content): ?string
    {
        if (preg_match('/class\s+\w+\s+extends\s+(\w+)/', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    private function usesSoftDeletes(string $content): bool
    {
        return str_contains($content, 'use SoftDeletes') || 
               str_contains($content, 'SoftDeletes');
    }

    private function usesTimestamps(string $content): bool
    {
        // Check if timestamps are explicitly disabled
        if (preg_match('/\$timestamps\s*=\s*(false|0)/', $content)) {
            return false;
        }
        
        // Laravel models use timestamps by default
        return true;
    }

    private function hasFactory(string $content): bool
    {
        return str_contains($content, 'use HasFactory') || 
               str_contains($content, 'HasFactory');
    }

    private function extractObservers(string $content): array
    {
        // This would require looking at service providers or boot methods
        // For now, return empty array
        return [];
    }

    private function extractValidationRules(string $content): array
    {
        $rules = [];
        
        // Look for validation rules in model
        if (preg_match('/\$rules\s*=\s*\[(.*?)\]/s', $content, $matches)) {
            $rulesArray = $matches[1];
            preg_match_all('/[\'"`]([^\'"`]+)[\'"`]\s*=>\s*[\'"`]([^\'"`]+)[\'"`]/', $rulesArray, $ruleMatches, PREG_SET_ORDER);
            
            foreach ($ruleMatches as $rule) {
                $rules[$rule[1]] = $rule[2];
            }
        }
        
        return $rules;
    }

    private function parseArrayValues(string $arrayContent): array
    {
        $values = [];
        preg_match_all('/[\'"`]([^\'"`]+)[\'"`]/', $arrayContent, $matches);
        
        return $matches[1];
    }

    private function parseRelationshipParameters(string $parameters): array
    {
        $params = [];
        $parts = explode(',', $parameters);
        
        foreach ($parts as $index => $part) {
            $part = trim($part);
            $part = str_replace(['\'', '"', '::class'], '', $part);
            
            switch ($index) {
                case 0:
                    $params['model'] = $part;
                    break;
                case 1:
                    $params['foreign_key'] = $part;
                    break;
                case 2:
                    $params['local_key'] = $part;
                    break;
                case 3: // For many-to-many relationships
                    $params['pivot_table'] = $part;
                    break;
            }
        }
        
        return $params;
    }

    private function camelToSnake(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    private function pluralize(string $word): string
    {
        // Simple pluralization rules
        if (str_ends_with($word, 'y')) {
            return substr($word, 0, -1) . 'ies';
        } elseif (str_ends_with($word, 's') || str_ends_with($word, 'x') || str_ends_with($word, 'z')) {
            return $word . 'es';
        } else {
            return $word . 's';
        }
    }

    private function findLineNumber(string $content, string $searchString): int
    {
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNumber => $line) {
            if (str_contains($line, $searchString)) {
                return $lineNumber + 1;
            }
        }
        
        return 1;
    }
}