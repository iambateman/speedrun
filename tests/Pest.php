<?php

use Iambateman\Speedrun\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

// Custom expectations for feature testing
expect()->extend('toBeValidFeatureName', function () {
    return $this->toMatch('/^[a-z0-9\-]+$/');
});

expect()->extend('toBeInPhase', function (string $phase) {
    return $this->toHaveProperty('phase', $phase);
});

expect()->extend('toHaveFeatureStructure', function () {
    $path = $this->value;
    expect(file_exists($path))->toBeTrue("Feature directory should exist: {$path}");
    expect(file_exists($path.'/planning'))->toBeTrue('Planning directory should exist');
    expect(file_exists($path.'/research'))->toBeTrue('Research directory should exist');
    expect(file_exists($path.'/assets'))->toBeTrue('Assets directory should exist');

    return $this;
});

// Helper functions
function createMockFeature(string $name = 'test-feature'): object
{
    return (object) [
        'name' => $name,
        'phase' => 'description',
        'path' => "/tmp/test/{$name}",
        'content' => 'Test content',
        'createdAt' => now(),
        'lastUpdated' => now(),
        'testPaths' => [],
        'codePaths' => [],
        'artifacts' => [],
    ];
}
