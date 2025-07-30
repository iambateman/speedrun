<?php

namespace Iambateman\Speedrun\Services;

use Iambateman\Speedrun\Models\Feature;
use Illuminate\Support\Facades\File;

class DirectoryManager
{
    public function __construct()
    {
        // Don't create directories in constructor - only when needed
    }

    public function ensureDirectoriesExist(): void
    {
        // Only create directories if package is installed
        if (!config('speedrun.installed', false)) {
            throw new \RuntimeException('Speedrun is not installed. Please run: php artisan speedrun:install');
        }

        $directories = [
            $this->getWipDirectory(),
            $this->getCompletedDirectory(),
            $this->getArchiveDirectory(),
        ];

        foreach ($directories as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
        }
    }

    public function createFeatureDirectory(string $name): string
    {
        $wipDir = $this->getWipDirectory();
        $path = $wipDir . '/' . $name;
        
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        return $path;
    }

    public function createFeatureSubdirectories(string $path): void
    {
        $subdirs = ['planning', 'research', 'assets'];
        
        foreach ($subdirs as $subdir) {
            $subdirPath = $path . '/' . $subdir;
            if (!File::exists($subdirPath)) {
                File::makeDirectory($subdirPath, 0755, true);
            }
        }
    }

    public function moveToCompleted(Feature $feature): string
    {
        $completedDir = $this->getCompletedDirectory();
        $newPath = $completedDir . '/' . $feature->name;
        
        if (File::exists($feature->path)) {
            File::moveDirectory($feature->path, $newPath);
        }
        
        $feature->path = $newPath;
        return $newPath;
    }

    public function moveToWip(Feature $feature): string
    {
        $wipDir = $this->getWipDirectory();
        $newPath = $wipDir . '/' . $feature->name;
        
        if (File::exists($feature->path)) {
            File::moveDirectory($feature->path, $newPath);
        }
        
        $feature->path = $newPath;
        return $newPath;
    }

    public function moveToArchive(Feature $feature): string
    {
        $archiveDir = $this->getArchiveDirectory();
        $newPath = $archiveDir . '/' . $feature->name;
        
        if (File::exists($feature->path)) {
            File::moveDirectory($feature->path, $newPath);
        }
        
        $feature->path = $newPath;
        return $newPath;
    }

    public function validateFeatureName(string $name): bool
    {
        $pattern = config('speedrun.features.naming.allowed_characters', '/^[a-z0-9\-]+$/');
        return preg_match($pattern, $name) === 1;
    }

    public function suggestFeatureName(string $name): string
    {
        // Convert to lowercase
        $name = strtolower($name);
        
        // Replace spaces and special characters with hyphens
        $name = preg_replace('/[^a-z0-9\-]/', '-', $name);
        
        // Remove multiple consecutive hyphens
        $name = preg_replace('/-+/', '-', $name);
        
        // Remove leading/trailing hyphens
        $name = trim($name, '-');
        
        return $name;
    }

    public function getWipDirectory(): string
    {
        $path = config('speedrun.features.directories.wip', 'docs/wip');
        return str_starts_with($path, '/') ? $path : base_path($path);
    }

    public function getCompletedDirectory(): string
    {
        $path = config('speedrun.features.directories.completed', 'docs/features');
        return str_starts_with($path, '/') ? $path : base_path($path);
    }

    public function getArchiveDirectory(): string
    {
        $path = config('speedrun.features.directories.archive', 'docs/archive');
        return str_starts_with($path, '/') ? $path : base_path($path);
    }
}