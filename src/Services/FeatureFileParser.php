<?php

namespace Iambateman\Speedrun\Services;

use Carbon\Carbon;
use Iambateman\Speedrun\Enums\FeaturePhase;
use Iambateman\Speedrun\Exceptions\CorruptedStateException;
use Iambateman\Speedrun\Models\Feature;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class FeatureFileParser
{
    public function parse(string $filePath): Feature
    {
        if (!File::exists($filePath)) {
            throw new \InvalidArgumentException("Feature file does not exist: {$filePath}");
        }

        $content = File::get($filePath);
        $parts = $this->extractFrontmatter($content);

        try {
            $frontmatter = empty($parts['frontmatter']) ? [] : Yaml::parse($parts['frontmatter']);
        } catch (ParseException $e) {
            throw new CorruptedStateException($filePath, 'Invalid YAML frontmatter: ' . $e->getMessage());
        }

        // Handle files without proper frontmatter
        if (empty($frontmatter)) {
            throw new CorruptedStateException($filePath, 'Missing required frontmatter fields');
        }

        // Extract feature name from frontmatter or filename
        $featureName = $frontmatter['feature_name'] ?? basename(dirname($filePath));
        
        return new Feature(
            name: $featureName,
            phase: FeaturePhase::from($frontmatter['phase']),
            path: dirname($filePath),
            content: $parts['content'],
            createdAt: Carbon::parse($frontmatter['created_at']),
            lastUpdated: Carbon::parse($frontmatter['last_updated']),
            parentFeature: $frontmatter['parent_feature'] ?? null,
            parentRelationship: $frontmatter['parent_relationship'] ?? null,
            testPaths: $frontmatter['test_paths'] ?? [],
            codePaths: $frontmatter['code_paths'] ?? [],
            artifacts: $frontmatter['artifacts'] ?? [],
            improvementHistory: $frontmatter['improvement_history'] ?? []
        );
    }

    public function save(Feature $feature): void
    {
        $filePath = $feature->path . '/_' . $feature->name . '.md';
        
        $frontmatter = [
            'phase' => $feature->phase->value,
            'feature_name' => $feature->name,
            'parent_feature' => $feature->parentFeature,
            'parent_relationship' => $feature->parentRelationship,
            'created_at' => $feature->createdAt->toDateString(),
            'last_updated' => $feature->lastUpdated->toDateString(),
            'test_paths' => $feature->testPaths,
            'code_paths' => $feature->codePaths,
            'artifacts' => $feature->artifacts,
            'improvement_history' => $feature->improvementHistory,
        ];

        // Remove null values
        $frontmatter = array_filter($frontmatter, fn($value) => $value !== null);

        $yaml = Yaml::dump($frontmatter, 4, 2);
        $content = "---\n{$yaml}---\n\n{$feature->content}";

        File::put($filePath, $content);
    }

    public function createNewFeature(
        string $name, 
        string $directory, 
        ?string $parentFeature = null,
        ?string $parentRelationship = null
    ): Feature {
        $content = $this->getDefaultFeatureContent($name);
        
        $feature = new Feature(
            name: $name,
            phase: FeaturePhase::DESCRIPTION,
            path: $directory,
            content: $content,
            createdAt: now(),
            lastUpdated: now(),
            parentFeature: $parentFeature,
            parentRelationship: $parentRelationship,
            testPaths: [],
            codePaths: [],
            artifacts: [
                'planning_docs' => [],
                'research_files' => [],
                'assets' => []
            ],
            improvementHistory: []
        );

        // Write the feature file immediately
        $this->save($feature);
        
        return $feature;
    }

    public function write(Feature $feature): void
    {
        $this->save($feature);
    }

    private function extractFrontmatter(string $content): array
    {
        if (!str_starts_with($content, '---')) {
            // Handle files without frontmatter - return just the content
            return [
                'frontmatter' => '',
                'content' => trim($content)
            ];
        }

        $parts = explode('---', $content, 3);
        
        if (count($parts) < 3) {
            throw new CorruptedStateException('', 'Invalid frontmatter structure');
        }

        return [
            'frontmatter' => trim($parts[1]),
            'content' => trim($parts[2])
        ];
    }

    private function getDefaultFeatureContent(string $name): string
    {
        $title = str_replace('-', ' ', ucwords($name, '-'));
        
        return <<<MD
# {$title}

## Overview
Feature description and purpose.

## Requirements
- Functional requirement 1
- Functional requirement 2
- Non-functional requirement 1

## Implementation Notes
Technical considerations and constraints.

## Dependencies
- Laravel Framework
- Other dependencies as needed

## Acceptance Criteria
- [ ] User can perform action A
- [ ] System validates input B
- [ ] Feature integrates with component C
MD;
    }
}