<?php

namespace Iambateman\Speedrun\Models;

use Carbon\Carbon;
use Iambateman\Speedrun\Enums\FeaturePhase;

class Feature
{
    public function __construct(
        public string $name,
        public FeaturePhase $phase,
        public string $path,
        public string $content,
        public Carbon $createdAt,
        public Carbon $lastUpdated,
        public ?string $parentFeature = null,
        public ?string $parentRelationship = null,
        public array $testPaths = [],
        public array $codePaths = [],
        public array $artifacts = [],
        public array $improvementHistory = []
    ) {}

    public function getCurrentPhase(): FeaturePhase
    {
        return $this->phase;
    }

    public function transitionTo(FeaturePhase $phase): void
    {
        $this->phase = $phase;
        $this->lastUpdated = now();
    }

    public function getCompletedPath(): string
    {
        return str_replace('/wip/', '/features/', $this->path);
    }

    public function getPlanningDocuments(): \Illuminate\Support\Collection
    {
        $planningPath = $this->path . '/planning';
        if (!is_dir($planningPath)) {
            return collect();
        }

        $files = glob($planningPath . '/_plan_*.md');
        return collect($files)->map(function ($file) {
            return (object) [
                'path' => $file,
                'relativePath' => str_replace($this->path . '/', '', $file),
                'filename' => basename($file),
            ];
        });
    }

    public function getResearchFiles(): \Illuminate\Support\Collection
    {
        $researchPath = $this->path . '/research';
        if (!is_dir($researchPath)) {
            return collect();
        }

        $files = glob($researchPath . '/*');
        return collect($files)->map(function ($file) {
            return (object) [
                'path' => $file,
                'relativePath' => str_replace($this->path . '/', '', $file),
                'filename' => basename($file),
            ];
        });
    }

    public function addCodePaths(array $paths): void
    {
        $this->codePaths = array_merge($this->codePaths, $paths);
    }

    public function addImprovementNote(string $improvement): void
    {
        $this->content .= "\n\n## Improvement Goal\n\n" . $improvement;
        $this->improvementHistory[] = [
            'goal' => $improvement,
            'date' => now()->toISOString(),
            'completed' => false
        ];
    }

    public function markCurrentImprovementComplete(): void
    {
        // Find the most recent incomplete improvement and mark it as complete
        for ($i = count($this->improvementHistory) - 1; $i >= 0; $i--) {
            if (!$this->improvementHistory[$i]['completed']) {
                $this->improvementHistory[$i]['completed'] = true;
                $this->improvementHistory[$i]['completed_date'] = now()->toISOString();
                break;
            }
        }
    }
}