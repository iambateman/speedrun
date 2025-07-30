<?php

use Iambateman\Speedrun\Services\DirectoryManager;
use Iambateman\Speedrun\Exceptions\FileSystemException;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->directoryManager = app(DirectoryManager::class);
});

describe('DirectoryManager', function () {

    it('ensures directory structure exists', function () {
        // Clean up first to test creation
        $this->cleanupTestDirectories();
        
        $this->directoryManager->ensureDirectoriesExist();
        
        expect(File::exists($this->testBasePath . '/wip'))->toBeTrue();
        expect(File::exists($this->testBasePath . '/features'))->toBeTrue();
        expect(File::exists($this->testBasePath . '/archive'))->toBeTrue();
        
        // Check .gitkeep files
        expect(File::exists($this->testBasePath . '/wip/.gitkeep'))->toBeTrue();
        expect(File::exists($this->testBasePath . '/features/.gitkeep'))->toBeTrue();
        expect(File::exists($this->testBasePath . '/archive/.gitkeep'))->toBeTrue();
    });

    it('creates feature directory structure', function () {
        $path = $this->directoryManager->createFeatureDirectory('dir-test');
        
        expect($path)->toBe($this->testBasePath . '/wip/dir-test');
        expect(File::exists($path))->toBeTrue();
    });

    it('throws exception for duplicate feature directory', function () {
        $this->directoryManager->createFeatureDirectory('duplicate-test');
        
        expect(fn () => $this->directoryManager->createFeatureDirectory('duplicate-test'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('creates feature subdirectories', function () {
        $path = $this->directoryManager->createFeatureDirectory('subdir-test');
        $this->directoryManager->createFeatureSubdirectories($path);
        
        expect(File::exists($path . '/planning'))->toBeTrue();
        expect(File::exists($path . '/research'))->toBeTrue();
        expect(File::exists($path . '/assets'))->toBeTrue();
        
        // Check .gitkeep files in subdirectories
        expect(File::exists($path . '/planning/.gitkeep'))->toBeTrue();
        expect(File::exists($path . '/research/.gitkeep'))->toBeTrue();
        expect(File::exists($path . '/assets/.gitkeep'))->toBeTrue();
    });

    it('moves features to completed directory', function () {
        $feature = createMockFeature('move-completed-test');
        $feature->path = $this->createFeatureFixture('move-completed-test');
        
        $newPath = $this->directoryManager->moveToCompleted($feature);
        
        expect($newPath)->toBe($this->testBasePath . '/features/move-completed-test');
        expect(File::exists($newPath))->toBeTrue();
        expect(File::exists($feature->path))->toBeFalse(); // Original should be gone
    });

    it('moves features back to wip directory', function () {
        $feature = createMockFeature('move-wip-test');
        $feature->path = $this->createCompletedFeatureFixture('move-wip-test');
        
        $newPath = $this->directoryManager->moveToWip($feature);
        
        expect($newPath)->toBe($this->testBasePath . '/wip/move-wip-test');
        expect(File::exists($newPath))->toBeTrue();
        expect(File::exists($feature->path))->toBeFalse();
    });

    it('archives features with timestamp', function () {
        $feature = createMockFeature('archive-test');
        $feature->path = $this->createFeatureFixture('archive-test');
        
        $archivedPath = $this->directoryManager->archiveFeature($feature);
        
        expect($archivedPath)->toContain($this->testBasePath . '/archive/archive-test_');
        expect(File::exists($archivedPath))->toBeTrue();
        expect(File::exists($feature->path))->toBeFalse();
    });

    it('validates feature names correctly', function () {
        expect($this->directoryManager->validateFeatureName('valid-name'))->toBeTrue();
        expect($this->directoryManager->validateFeatureName('valid-name-123'))->toBeTrue();
        expect($this->directoryManager->validateFeatureName('123-valid'))->toBeTrue();
        
        expect($this->directoryManager->validateFeatureName('Invalid-Name'))->toBeFalse();
        expect($this->directoryManager->validateFeatureName('invalid name'))->toBeFalse();
        expect($this->directoryManager->validateFeatureName('invalid_name'))->toBeFalse();
        expect($this->directoryManager->validateFeatureName('invalid.name'))->toBeFalse();
        expect($this->directoryManager->validateFeatureName(''))->toBeFalse();
    });

    it('suggests valid feature names', function () {
        expect($this->directoryManager->suggestFeatureName('My Feature Name'))
            ->toBe('my-feature-name');
        expect($this->directoryManager->suggestFeatureName('Feature___Name!!!'))
            ->toBe('feature-name');
        expect($this->directoryManager->suggestFeatureName('___start-and-end___'))
            ->toBe('start-and-end');
        expect($this->directoryManager->suggestFeatureName('Multiple   Spaces'))
            ->toBe('multiple-spaces');
        expect($this->directoryManager->suggestFeatureName('Under_Score_Name'))
            ->toBe('under-score-name');
    });

    it('returns correct path accessors', function () {
        expect($this->directoryManager->getWipPath('test-feature'))
            ->toBe($this->testBasePath . '/wip/test-feature');
        expect($this->directoryManager->getCompletedPath('test-feature'))
            ->toBe($this->testBasePath . '/features/test-feature');
        expect($this->directoryManager->getWipDirectory())
            ->toBe($this->testBasePath . '/wip');
        expect($this->directoryManager->getCompletedDirectory())
            ->toBe($this->testBasePath . '/features');
        expect($this->directoryManager->getArchiveDirectory())
            ->toBe($this->testBasePath . '/archive');
    });

    it('calculates feature size correctly', function () {
        $path = $this->createFeatureFixture('size-test');
        File::put($path . '/large-file.txt', str_repeat('a', 1000));
        
        $size = $this->directoryManager->getFeatureSize($path);
        
        expect($size)->toBeGreaterThan(1000); // At least the large file size
    });

    it('counts feature files by type', function () {
        $path = $this->createFeatureFixture('count-test');
        File::put($path . '/planning/plan.md', 'planning content');
        File::put($path . '/research/notes.txt', 'research notes');
        File::put($path . '/assets/image.png', 'fake image');
        
        $count = $this->directoryManager->getFeatureFileCount($path);
        
        expect($count['total'])->toBeGreaterThanOrEqual(4); // At least 4 files (feature file + 3 created)
        expect($count['by_type'])->toHaveKey('md');
        expect($count['by_type'])->toHaveKey('txt');
        expect($count['by_type'])->toHaveKey('png');
    });

    it('cleans up empty directories', function () {
        // Create nested empty directory structure
        $emptyPath = $this->testBasePath . '/wip/empty-parent/empty-child';
        File::makeDirectory($emptyPath, 0755, true);
        
        // Create directory with only .gitkeep
        $gitkeepPath = $this->testBasePath . '/wip/gitkeep-only';
        File::makeDirectory($gitkeepPath, 0755, true);
        File::put($gitkeepPath . '/.gitkeep', '');
        
        // Create directory with actual content
        $contentPath = $this->createFeatureFixture('has-content');
        
        $this->directoryManager->cleanupEmptyDirectories($this->testBasePath . '/wip');
        
        // Empty directories should be removed
        expect(File::exists($emptyPath))->toBeFalse();
        expect(File::exists($gitkeepPath))->toBeFalse();
        
        // Directory with content should remain
        expect(File::exists($contentPath))->toBeTrue();
    });

    it('copies features correctly', function () {
        $sourcePath = $this->createFeatureFixture('source-feature');
        File::put($sourcePath . '/planning/plan.md', 'planning content');
        
        $destinationPath = $this->directoryManager->copyFeature($sourcePath, 'copied-feature');
        
        expect($destinationPath)->toBe($this->testBasePath . '/wip/copied-feature');
        expect(File::exists($destinationPath))->toBeTrue();
        expect(File::exists($destinationPath . '/planning/plan.md'))->toBeTrue();
        expect(File::exists($sourcePath))->toBeTrue(); // Original should still exist
    });

    it('updates feature name when copying', function () {
        $sourcePath = $this->createFeatureFixture('original-name');
        
        $destinationPath = $this->directoryManager->copyFeature($sourcePath, 'new-name');
        
        // Check that feature file was renamed
        expect(File::exists($destinationPath . '/_new-name.md'))->toBeTrue();
        expect(File::exists($destinationPath . '/_original-name.md'))->toBeFalse();
        
        // Check that frontmatter was updated
        $content = File::get($destinationPath . '/_new-name.md');
        expect($content)->toContain('feature_name: new-name');
        expect($content)->not->toContain('feature_name: original-name');
    });

    it('throws exception when copying to existing destination', function () {
        $sourcePath = $this->createFeatureFixture('source-duplicate');
        $this->createFeatureFixture('destination-duplicate');
        
        expect(fn () => $this->directoryManager->copyFeature($sourcePath, 'destination-duplicate'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('handles missing source when moving', function () {
        $feature = createMockFeature('missing-source');
        $feature->path = $this->testBasePath . '/wip/nonexistent';
        
        expect(fn () => $this->directoryManager->moveToCompleted($feature))
            ->toThrow(InvalidArgumentException::class);
    });

    it('handles existing destination when moving', function () {
        $feature = createMockFeature('existing-destination');
        $feature->path = $this->createFeatureFixture('existing-destination');
        
        // Create destination manually
        File::makeDirectory($this->testBasePath . '/features/existing-destination', 0755, true);
        
        expect(fn () => $this->directoryManager->moveToCompleted($feature))
            ->toThrow(InvalidArgumentException::class);
    });

    it('creates parent directories when needed', function () {
        $feature = createMockFeature('needs-parent');
        $feature->path = $this->createFeatureFixture('needs-parent');
        
        // Remove the features directory to test parent creation
        File::deleteDirectory($this->testBasePath . '/features');
        
        $newPath = $this->directoryManager->moveToCompleted($feature);
        
        expect(File::exists(dirname($newPath)))->toBeTrue();
        expect(File::exists($newPath))->toBeTrue();
    });

});