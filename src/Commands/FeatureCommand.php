<?php

namespace Iambateman\Speedrun\Commands;

use Iambateman\Speedrun\Enums\FeaturePhase;
use Iambateman\Speedrun\Services\DirectoryManager;
use Iambateman\Speedrun\Services\FeatureStateManager;
use Illuminate\Console\Command;

class FeatureCommand extends Command
{
    protected $signature = 'speedrun:feature {name} {--force}';

    protected $description = 'Manage feature lifecycle - requires feature name as argument';

    public function __construct(
        private FeatureStateManager $stateManager,
        private DirectoryManager $directoryManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->checkProductionSafety();
        $this->ensureFeatureDirectoriesExist();

        $featureName = $this->argument('name');

        // Validate feature name format
        if (! $this->isValidFeatureName($featureName)) {
            $this->error("Invalid feature name: '{$featureName}'");
            $this->line('Feature names must be kebab-case (lowercase letters, numbers, and hyphens only).');

            $suggestion = $this->generateFeatureNameSuggestion($featureName);
            if ($suggestion && $suggestion !== $featureName) {
                $this->line("💡 Suggested name: <info>{$suggestion}</info>");

                if ($this->confirm("Use suggested name '{$suggestion}'?")) {
                    $featureName = $suggestion;
                } else {
                    return Command::FAILURE;
                }
            } else {
                return Command::FAILURE;
            }
        }

        // Check if feature exists
        $feature = $this->stateManager->findFeature($featureName);

        if (! $feature) {
            // Create new feature
            $this->info("Creating new feature: {$featureName}");

            return $this->call('speedrun:feature-discover', ['--create' => $featureName]);
        }

        // Resume existing feature based on phase
        $phase = $feature->getCurrentPhase();
        $this->line("Resuming feature '{$featureName}' in {$phase->value} phase");

        return match ($phase) {
            FeaturePhase::DESCRIPTION => $this->call('speedrun:feature-describe', ['name' => $featureName]),
            FeaturePhase::PLANNING => $this->call('speedrun:feature-plan', ['name' => $featureName]),
            FeaturePhase::EXECUTION => $this->call('speedrun:feature-execute', ['name' => $featureName]),
            FeaturePhase::CLEANUP => $this->call('speedrun:feature-cleanup', ['name' => $featureName]),
            FeaturePhase::COMPLETE => $this->handleCompleteFeature($featureName),
            default => $this->call('speedrun:feature-describe', ['name' => $featureName]),
        };
    }

    private function handleCompleteFeature(string $featureName): int
    {
        $this->warn("Feature '{$featureName}' is already complete.");

        if ($this->confirm('Would you like to improve this feature?')) {
            return $this->call('speedrun:feature-improve', ['name' => $featureName]);
        }

        return Command::SUCCESS;
    }

    private function checkProductionSafety(): void
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('This command is disabled in production. Use --force to override.');
            exit(1);
        }
    }

    private function ensureFeatureDirectoriesExist(): void
    {
        $this->directoryManager->ensureDirectoriesExist();
    }

    private function isValidFeatureName(string $name): bool
    {
        return preg_match('/^[a-z0-9\-]+$/', $name) === 1;
    }

    private function generateFeatureNameSuggestion(string $input): ?string
    {
        try {
            $client = new \OpenAI\Client(env('SPEEDRUN_OPENAI_API_KEY'));

            $prompt = "Convert this feature description to a short, pithy folder name using kebab-case (lowercase letters, numbers, and hyphens only). Keep it under 30 characters and make it descriptive but concise. Examples:
            
'Get contracts into S3' -> 's3-contracts-sync'
'User authentication system' -> 'user-auth'
'Payment processing workflow' -> 'payment-processing'
'Email notification service' -> 'email-notifications'

Feature description: {$input}

Return only the suggested name, nothing else:";

            $response = $client->chat()->create([
                'model' => config('speedrun.describe.openai_model', 'gpt-3.5-turbo'),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 50,
                'temperature' => 0.3,
            ]);

            $suggestion = trim($response->choices[0]->message->content);

            // Validate the AI suggestion
            if ($this->isValidFeatureName($suggestion) && strlen($suggestion) <= 30) {
                return $suggestion;
            }

            // Fallback: create a basic kebab-case version
            return $this->createBasicKebabCase($input);

        } catch (\Exception $e) {
            // Fallback if AI fails
            return $this->createBasicKebabCase($input);
        }
    }

    private function createBasicKebabCase(string $input): string
    {
        // Convert to lowercase, replace spaces and special chars with hyphens
        $kebab = strtolower($input);
        $kebab = preg_replace('/[^a-z0-9]+/', '-', $kebab);
        $kebab = trim($kebab, '-');

        // Limit length
        if (strlen($kebab) > 30) {
            $kebab = substr($kebab, 0, 30);
            $kebab = rtrim($kebab, '-');
        }

        return $kebab ?: 'feature';
    }
}
