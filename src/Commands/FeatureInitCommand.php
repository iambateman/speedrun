<?php

namespace Iambateman\Speedrun\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FeatureInitCommand extends Command
{
    protected $signature = 'speedrun:feature:init {slug}';

    protected $description = 'Initialize a new feature with directories and markdown file';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $slug = $this->argument('slug');

        // Validate slug format (kebab-case)
        if (!$this->isValidSlug($slug)) {
            $this->error("Invalid slug format: '{$slug}'");
            $this->line('Slugs must be kebab-case (lowercase letters, numbers, and hyphens only).');
            return Command::FAILURE;
        }

        // Ensure base directories exist
        $this->ensureDirectoriesExist();

        // Create feature directory structure
        $featurePath = $this->createFeatureDirectories($slug);
        
        if (empty($featurePath)) {
            return Command::FAILURE;
        }
        
        // Create the feature markdown file
        $markdownPath = $this->createFeatureMarkdown($slug, $featurePath);

        $this->info("✅ Feature '{$slug}' initialized successfully!");
        $this->line("📁 Feature directory: {$featurePath}");
        $this->line("📄 Feature file: {$markdownPath}");
        $this->newLine();
        
        $this->warn("AI Instructions:");
        $this->line("Work with the user to build out a full description of the '{$slug}' feature.");
        $this->line("Ask questions to understand:");
        $this->line("  • What the feature should do");
        $this->line("  • Who will use it");
        $this->line("  • Technical requirements");
        $this->line("  • Dependencies and integrations");
        $this->line("  • Success criteria");
        $this->newLine();
        $this->line("IMPORTANT: You are in a planning phase. Do NOT recommend creating new project code at this stage.");
        $this->line("Success looks like getting a really great document at {$markdownPath}");
        $this->newLine();
        $this->line("When you're done, update the feature file and ask the user to clarify their goals for the feature.");
        $this->line("Update the feature file at: {$markdownPath}");

        return Command::SUCCESS;
    }

    private function isValidSlug(string $slug): bool
    {
        return preg_match('/^[a-z0-9\-]+$/', $slug) === 1;
    }

    private function createFeatureDirectories(string $slug): string
    {
        $wipPath = $this->getWipDirectory();
        $featurePath = "{$wipPath}/{$slug}";

        if (File::exists($featurePath)) {
            $this->error("❌ Feature slug '{$slug}' is already in use.");
            $this->line("AI: Please inform the user that the slug '{$slug}' is already in use and ask them to choose a different slug.");
            return '';
        }

        File::makeDirectory($featurePath, 0755, true);
        $this->line("📁 Created directory: {$featurePath}");

        return $featurePath;
    }

    private function createFeatureMarkdown(string $slug, string $featurePath): string
    {
        $markdownPath = "{$featurePath}/_{$slug}.md";

        if (File::exists($markdownPath)) {
            $this->warn("⚠️  Feature file already exists: {$markdownPath}");
            return $markdownPath;
        }

        // Load the stub content
        $stubPath = __DIR__ . '/../../resources/stubs/feature.md';
        $stubContent = File::get($stubPath);

        // Replace the placeholder with the actual slug
        $content = str_replace('{ FEATURE SLUG }', $slug, $stubContent);

        // Write the file
        File::put($markdownPath, $content);
        $this->line("📄 Created feature file: {$markdownPath}");

        return $markdownPath;
    }

    private function ensureDirectoriesExist(): void
    {
        $directories = [
            $this->getWipDirectory(),
            $this->getCompletedDirectory(),
        ];

        foreach ($directories as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
        }
    }

    private function getWipDirectory(): string
    {
        return base_path(config('speedrun.directory', '_docs') . '/wip');
    }

    private function getCompletedDirectory(): string
    {
        return base_path(config('speedrun.directory', '_docs') . '/features');
    }


}
