<?php

namespace Iambateman\Speedrun\Commands;

use Iambateman\Speedrun\Services\CodebaseAnalyzer;
use Iambateman\Speedrun\Services\DirectoryManager;
use Iambateman\Speedrun\Services\FeatureFileParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CodebaseDescribeCommand extends Command
{
    protected $signature = 'speedrun:describe {target?} {--feature=} {--depth=3} {--output=features/discovered} {--dry-run}';
    protected $description = 'Analyze codebase and generate feature definitions';

    public function __construct(
        private CodebaseAnalyzer $analyzer,
        private DirectoryManager $directoryManager,
        private FeatureFileParser $featureParser
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->checkProductionSafety();
        $this->displayHeader();

        $basePath = $this->getBasePath();
        $options = $this->getAnalysisOptions();

        // Validate the target path
        if (!File::isDirectory($basePath)) {
            $this->error("Target directory does not exist: {$basePath}");
            return Command::FAILURE;
        }

        $this->info("🔍 Analyzing codebase at: {$basePath}");
        $this->newLine();

        try {
            // Perform analysis
            $startTime = microtime(true);
            
            if ($this->option('feature')) {
                $features = collect([$this->analyzer->analyzeSpecificFeature($basePath, $this->option('feature'), $options)]);
                $features = $features->filter(); // Remove null values
            } else {
                $features = $this->analyzer->analyzeCodebase($basePath, $options);
            }

            $analysisTime = round((microtime(true) - $startTime), 2);
            
            if ($features->isEmpty()) {
                $this->warn('No features could be extracted from the codebase.');
                return Command::SUCCESS;
            }

            $this->displayAnalysisResults($features, $analysisTime);

            // Generate feature files
            if (!$this->option('dry-run')) {
                $this->generateFeatureFiles($features, $options);
            } else {
                $this->info("🧪 Dry run completed. No files were created.");
            }

            $this->displaySummary($features);

        } catch (\Exception $e) {
            $this->error("Analysis failed: " . $e->getMessage());
            
            if ($this->getOutput()->isVerbose()) {
                $this->error($e->getTraceAsString());
            }
            
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function checkProductionSafety(): void
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('This command is disabled in production. Use --force to override.');
            exit(1);
        }
    }

    private function displayHeader(): void
    {
        $this->info('🚀 Speedrun Codebase Analyzer');
        $this->info('═══════════════════════════════');
        $this->newLine();
    }

    private function getBasePath(): string
    {
        $target = $this->argument('target');
        
        if ($target) {
            return realpath($target) ?: $target;
        }

        // Default to current working directory (Laravel project root)
        return getcwd();
    }

    private function getAnalysisOptions(): array
    {
        return [
            'analysis_depth' => (int) $this->option('depth'),
            'include_tests' => true,
            'include_views' => true,
            'exclude_patterns' => config('speedrun.describe.exclude_patterns', [
                'vendor/*',
                'node_modules/*',
                'storage/*',
                '.git/*'
            ]),
            'output_directory' => $this->option('output')
        ];
    }

    private function displayAnalysisResults($features, float $analysisTime): void
    {
        $this->info("✅ Analysis completed in {$analysisTime}s");
        $this->newLine();

        $this->table(
            ['Feature', 'Routes', 'Controllers', 'Models', 'Views', 'Tests'],
            $features->map(function ($feature) {
                return [
                    $feature['title'],
                    count($feature['routes']),
                    count($feature['controllers']),
                    count($feature['models']),
                    count($feature['views']),
                    count($feature['tests'])
                ];
            })->toArray()
        );

        $this->newLine();
    }

    private function generateFeatureFiles($features, array $options): void
    {
        $outputPath = base_path($options['output_directory']);
        
        // Ensure output directory exists
        if (!File::isDirectory($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
            $this->info("📁 Created output directory: {$outputPath}");
        }

        $generatedCount = 0;

        foreach ($features as $feature) {
            $featurePath = $outputPath . '/' . $feature['name'];
            
            // Create feature directory
            if (!File::isDirectory($featurePath)) {
                File::makeDirectory($featurePath, 0755, true);
            }

            // Generate feature file
            $featureFile = $featurePath . '/_' . $feature['name'] . '.md';
            
            if (File::exists($featureFile) && !$this->confirm("Feature file already exists: {$featureFile}. Overwrite?")) {
                continue;
            }

            $this->createFeatureFile($featureFile, $feature);
            $generatedCount++;

            $this->line("📝 Generated: {$featureFile}");
        }

        $this->newLine();
        $this->info("✨ Generated {$generatedCount} feature files in {$outputPath}");
    }

    private function createFeatureFile(string $filePath, array $feature): void
    {
        // Create frontmatter
        $frontmatter = [
            'phase' => 'description',
            'feature_name' => $feature['name'],
            'parent_feature' => null,
            'parent_relationship' => null,
            'created_at' => now()->toDateString(),
            'last_updated' => now()->toDateString(),
            'test_paths' => $feature['test_paths'],
            'code_paths' => $feature['code_paths'],
            'artifacts' => [
                'planning_docs' => [],
                'research_files' => [],
                'assets' => []
            ]
        ];

        // Convert to YAML
        $yaml = \Symfony\Component\Yaml\Yaml::dump($frontmatter, 4, 2);
        
        // Build full content
        $content = "---\n{$yaml}---\n\n{$feature['content']}";
        
        File::put($filePath, $content);
    }

    private function displaySummary($features): void
    {
        $totalRoutes = $features->sum(fn($f) => count($f['routes']));
        $totalControllers = $features->sum(fn($f) => count($f['controllers']));
        $totalModels = $features->sum(fn($f) => count($f['models']));
        $totalViews = $features->sum(fn($f) => count($f['views']));
        $totalTests = $features->sum(fn($f) => count($f['tests']));

        $this->newLine();
        $this->info('📊 Analysis Summary');
        $this->info('─────────────────');
        $this->line("Features discovered: {$features->count()}");
        $this->line("Routes analyzed: {$totalRoutes}");
        $this->line("Controllers analyzed: {$totalControllers}");
        $this->line("Models analyzed: {$totalModels}");
        $this->line("Views analyzed: {$totalViews}");
        $this->line("Tests analyzed: {$totalTests}");
        
        $this->newLine();
        $this->info('🎯 Next Steps:');
        $this->line('• Review generated feature files');
        $this->line('• Refine feature descriptions as needed');
        $this->line('• Use `php artisan speedrun:feature <name>` to work with features');
        $this->line('• Run tests to validate feature coverage');
    }
}