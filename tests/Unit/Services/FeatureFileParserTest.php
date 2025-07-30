<?php

use Iambateman\Speedrun\Services\FeatureFileParser;
use Iambateman\Speedrun\Enums\FeaturePhase;
use Iambateman\Speedrun\Exceptions\CorruptedStateException;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->parser = app(FeatureFileParser::class);
});

describe('FeatureFileParser', function () {

    it('parses valid feature files correctly', function () {
        $path = $this->createFeatureFixture('parse-test', [
            'frontmatter' => [
                'phase' => 'planning',
                'parent_feature' => '../parent/_parent.md',
                'test_paths' => ['tests/Feature/ParseTest.php'],
                'code_paths' => ['app/Models/ParseTest.php'],
                'artifacts' => [
                    'planning_docs' => ['planning/_plan_controller.md'],
                    'research_files' => ['research/notes.md'],
                ]
            ],
            'content' => 'Custom content for parse test'
        ]);
        
        $feature = $this->parser->parse($path . '/_parse-test.md');
        
        expect($feature->name)->toBe('parse-test');
        expect($feature->phase)->toBe(FeaturePhase::PLANNING);
        expect($feature->parentFeature)->toBe('../parent/_parent.md');
        expect($feature->testPaths)->toContain('tests/Feature/ParseTest.php');
        expect($feature->codePaths)->toContain('app/Models/ParseTest.php');
        expect($feature->artifacts['planning_docs'])->toContain('planning/_plan_controller.md');
        expect($feature->content)->toContain('Custom content for parse test');
    });

    it('throws exception for missing files', function () {
        expect(fn () => $this->parser->parse('/nonexistent/path.md'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('throws exception for corrupted frontmatter', function () {
        $path = $this->createCorruptedFeature('corrupted-frontmatter');
        
        expect(fn () => $this->parser->parse($path . '/_corrupted-frontmatter.md'))
            ->toThrow(CorruptedStateException::class);
    });

    it('handles missing frontmatter gracefully', function () {
        $path = $this->testBasePath . '/wip/no-frontmatter';
        File::makeDirectory($path, 0755, true);
        File::put($path . '/_no-frontmatter.md', "# Feature without frontmatter\n\nThis has no YAML frontmatter.");
        
        expect(fn () => $this->parser->parse($path . '/_no-frontmatter.md'))
            ->toThrow(CorruptedStateException::class);
    });

    it('writes feature files with correct format', function () {
        $feature = createMockFeature('write-test');
        $feature->path = $this->testBasePath . '/wip/write-test';
        $feature->testPaths = ['tests/Feature/WriteTest.php'];
        $feature->codePaths = ['app/Models/WriteTest.php'];
        
        File::makeDirectory($feature->path, 0755, true);
        $this->parser->write($feature);
        
        $filePath = $feature->path . '/_write-test.md';
        expect(File::exists($filePath))->toBeTrue();
        
        $content = File::get($filePath);
        expect($content)->toContain('---');
        expect($content)->toContain('phase: description');
        expect($content)->toContain('feature_name: write-test');
        expect($content)->toContain('Test content');
    });

    it('creates new feature with default content', function () {
        $path = $this->testBasePath . '/wip/new-feature';
        File::makeDirectory($path, 0755, true);
        
        $feature = $this->parser->createNewFeature('new-feature', $path);
        
        expect($feature->name)->toBe('new-feature');
        expect($feature->phase)->toBe(FeaturePhase::DESCRIPTION);
        expect($feature->content)->toContain('## Overview');
        expect($feature->content)->toContain('## Requirements');
        expect($feature->content)->toContain('## Acceptance Criteria');
        
        // Verify file was written
        $filePath = $path . '/_new-feature.md';
        expect(File::exists($filePath))->toBeTrue();
    });

    it('creates new feature with parent relationship', function () {
        $path = $this->testBasePath . '/wip/child-feature';
        File::makeDirectory($path, 0755, true);
        
        $feature = $this->parser->createNewFeature(
            name: 'child-feature',
            directory: $path,
            parentFeature: '../parent/_parent.md',
            parentRelationship: 'Extends parent functionality'
        );
        
        expect($feature->parentFeature)->toBe('../parent/_parent.md');
        expect($feature->parentRelationship)->toBe('Extends parent functionality');
    });

    it('preserves timestamps when parsing existing files', function () {
        $createdAt = '2025-01-01';
        $lastUpdated = '2025-01-15';
        
        $path = $this->createFeatureFixture('timestamp-test', [
            'frontmatter' => [
                'created_at' => $createdAt,
                'last_updated' => $lastUpdated,
            ]
        ]);
        
        $feature = $this->parser->parse($path . '/_timestamp-test.md');
        
        expect($feature->createdAt->toDateString())->toBe($createdAt);
        expect($feature->lastUpdated->toDateString())->toBe($lastUpdated);
    });

    it('handles empty arrays in frontmatter', function () {
        $path = $this->createFeatureFixture('empty-arrays', [
            'frontmatter' => [
                'test_paths' => [],
                'code_paths' => [],
                'artifacts' => [
                    'planning_docs' => [],
                    'research_files' => [],
                    'assets' => []
                ]
            ]
        ]);
        
        $feature = $this->parser->parse($path . '/_empty-arrays.md');
        
        expect($feature->testPaths)->toBeArray()->toBeEmpty();
        expect($feature->codePaths)->toBeArray()->toBeEmpty();
        expect($feature->artifacts['planning_docs'])->toBeArray()->toBeEmpty();
    });

    it('extracts feature name from path when missing from frontmatter', function () {
        $path = $this->testBasePath . '/wip/path-name-test';
        File::makeDirectory($path, 0755, true);
        
        $frontmatter = [
            'phase' => 'description',
            'created_at' => '2025-07-30',
            'last_updated' => '2025-07-30',
        ];
        
        $yaml = \Symfony\Component\Yaml\Yaml::dump($frontmatter);
        $content = "---\n{$yaml}---\n\n# Test Feature";
        File::put($path . '/_path-name-test.md', $content);
        
        $feature = $this->parser->parse($path . '/_path-name-test.md');
        
        expect($feature->name)->toBe('path-name-test');
    });

    it('handles special characters in content', function () {
        $specialContent = "# Feature with Special Characters\n\n- Bullet point\n- Another bullet\n\n```php\n\$code = 'example';\n```\n\n> Blockquote text";
        
        $path = $this->createFeatureFixture('special-chars', [
            'content' => $specialContent
        ]);
        
        $feature = $this->parser->parse($path . '/_special-chars.md');
        
        expect($feature->content)->toBe($specialContent);
    });

    it('validates required fields in frontmatter', function () {
        $path = $this->testBasePath . '/wip/invalid-phase';
        File::makeDirectory($path, 0755, true);
        
        $frontmatter = [
            'phase' => 'invalid-phase-name',
            'feature_name' => 'invalid-phase',
            'created_at' => '2025-07-30',
            'last_updated' => '2025-07-30',
        ];
        
        $yaml = \Symfony\Component\Yaml\Yaml::dump($frontmatter);
        $content = "---\n{$yaml}---\n\n# Test";
        File::put($path . '/_invalid-phase.md', $content);
        
        expect(fn () => $this->parser->parse($path . '/_invalid-phase.md'))
            ->toThrow(ValueError::class); // FeaturePhase enum will throw ValueError for invalid values
    });

    it('roundtrips feature data correctly', function () {
        // Create a feature with complex data
        $originalFeature = createMockFeature('roundtrip-test');
        $originalFeature->path = $this->testBasePath . '/wip/roundtrip-test';
        $originalFeature->phase = FeaturePhase::PLANNING;
        $originalFeature->testPaths = ['tests/Feature/RoundtripTest.php'];
        $originalFeature->codePaths = ['app/Models/Roundtrip.php'];
        $originalFeature->artifacts = [
            'planning_docs' => ['planning/_plan_model.md'],
            'research_files' => ['research/notes.md'],
            'assets' => ['assets/diagram.png']
        ];
        
        File::makeDirectory($originalFeature->path, 0755, true);
        
        // Write and then read back
        $this->parser->write($originalFeature);
        $reloadedFeature = $this->parser->parse($originalFeature->path . '/_roundtrip-test.md');
        
        expect($reloadedFeature->name)->toBe($originalFeature->name);
        expect($reloadedFeature->phase)->toBe($originalFeature->phase);
        expect($reloadedFeature->testPaths)->toBe($originalFeature->testPaths);
        expect($reloadedFeature->codePaths)->toBe($originalFeature->codePaths);
        expect($reloadedFeature->artifacts)->toBe($originalFeature->artifacts);
    });

});