<?php

namespace Iambateman\Speedrun\Actions\Tools;

use Iambateman\Speedrun\Actions\Tasks\GetTask;
use Iambateman\Speedrun\Actions\Utilities\FilterPHP;
use Iambateman\Speedrun\Actions\Utilities\GetAIWithFallback;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;


class MakeMigrationToCreateModel {

    use AsAction;

    public string $commandSignature = 'speedrun:make-migration-to-create-model {model_name}';

    public string $model_name;
    public string $prompt;
    public array $task;
    public string $file_path;
    public bool $success = false;
    public string $message = '';

    public function handle(string $model_name, string $task_path)
    {
        // Get initial data
        $this->model_name = $this->handleModelName($model_name);
        $this->task = GetTask::run($task_path);

        $this->buildPrompt();

        // Run the AI request
        $response = GetAIWithFallback::run($this->prompt);
        $response = FilterPHP::run($response); // Clean the PHP file
        $response = $this->verifyValidMigrationFile($response); // Validate the PHP file


        // Process the response
        if ($response) {
            $this->placeFile($response);

            CheckForBugs::run($this->file_path);
        }
    }

    public function asCommand(Command $command)
    {
        $model_name = $command->argument('model_name');
        $task_path = $command->argument('task_path');


        $command->info("Creating migration for $model_name");

        $this->handle($model_name, $task_path);

        if ($this->message) {
            $command->info($this->message);
        }
        ($this->success) ?
            $command->info("Successfully created migration for $model_name") :
            $command->warn("Failed making migration for $model_name");
    }

    public function placeFile(string $response)
    {
        $date = now()->format('Y_m_d_His');
        $this->file_path = base_path("database/migrations/{$date}_create_{$this->model_name}_table.php");

        $this->success = File::put($this->file_path, $response);
    }

    public function buildPrompt(): self
    {

        $this->prompt = Speedrun::getOverview();
        $this->prompt .= "You are creating a new migration for the {$this->model_name} model.";
        $this->prompt .= " The fields to include are " . $this->createFieldsString() . '.';

        if ($relationships = $this->createRelationshipsString()) {
            $this->prompt .= " All relationships for this task (possibly including some irrelevant ones) are " . $relationships . '.';
            $this->prompt .= " Include relevant belongsTo relationships for the {$this->model_name} table.";
            $this->prompt .= " Do not include extra 'many to many' schemas – only create a single schema for {$this->model_name}.";
        }

        $this->prompt .= " Do not include schemas for any other tables – only create a single schema for {$this->model_name}.";
        $this->prompt .= " Use an anonymous function for the migration. The class is declared like this: `return new class extends Migration {` and NOT like this `class Create{$this->model_name}Table extends Migration` ";
        $this->prompt .= " Fields marked * are required. Fields not marked * are nullable. Use regular Laravel timestamps. Respond only with the Laravel migration file, including all fields which should be present.";
        $this->prompt .= " A sample Laravel migration template is included below:\n\n";
        $this->prompt .= " ```php\n";
        $this->prompt .= $this->getSampleMigration();
        $this->prompt .= "\n```";

        return $this;
    }

    protected function getSampleMigration(): string
    {
        $path = base_path('vendor/iambateman/speedrun/resources/stubs/migration.php.stub');
        return File::get($path);
    }

    protected function handleModelName($model_name)
    {
        return str($model_name)->classBasename()->singular()->lower();
    }

    protected function createFieldsString(): string
    {
        $titleCaseModel = str($this->model_name)->title()->toString();
        return collect($this->task['Models'][$titleCaseModel])
            ->implode(', ');
    }

    protected function createRelationshipsString(): string
    {
        return collect($this->task['Relationships'] ?? [])
            ->implode(', ');
    }

    protected function verifyValidMigrationFile(string $response): string
    {
        return match (str($response)->substrCount('Schema::create')) {
            0 => '', // If there are no schemas, we messed up
            1 => $response,  // If there is one, we are good
            default => $this->filterMigration($response) // If there are multiple, let's remove the extras.
        };
    }

    protected function filterMigration(string $response): string
    {
        $prompt = "Below is a Laravel migration with multiple schemas declared. Remove all schemas except the first one.\n";
        $prompt .= $response;

        $response = GetAIWithFallback::run($prompt);
        return FilterPHP::run($response);
    }


}