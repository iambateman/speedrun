<?php

namespace Iambateman\Speedrun\Actions\Tools;

use Iambateman\Speedrun\Actions\Tasks\AddLogToTask;
use Iambateman\Speedrun\Actions\Tasks\GetTask;
use Iambateman\Speedrun\Actions\Utilities\FilterPHP;
use Iambateman\Speedrun\Actions\Utilities\GetAIWithFallback;
use Iambateman\Speedrun\Exceptions\ConfusedLLMException;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;


class MakeMigrationToCreateModel {

    use AsAction;

    public string $commandSignature = 'speedrun:make-migration-to-create-model {model_name}';

    public string $model_name;
    public string $model_table;
    public string $prompt;
    public array $task;
    public string $task_path;
    public string $response = ''; // AI response
    public string $file_path;
    public bool $success = false;
    public string $message = '';

    public function handle(string $model_name, string $task_path)
    {

        $this->setInitialData($model_name, $task_path)
            ->buildPrompt()
            ->runAI()
            ->filterPHP()
            ->verifyValidMigrationFile()
            ->confirmHasResponse()
            ->fixSpecificMigrationIssues()
            ->placeFile();

        CheckForBugs::run($this->file_path, "\nPARTICULAR NOTES: \n - ensure there is a down() function in the migration.\n - Make sure the model name is plural.");

        AddLogToTask::run(
            task_path: $task_path,
            log: "Created {$this->file_path}"
        );

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

    protected function setInitialData(string $model_name, string $task_path): self
    {
        $this->model_name = $model_name;
        $this->model_table = str($model_name)->lower()->plural()->toString();
        $this->task_path = $task_path;
        $this->task = GetTask::run($task_path);

        return $this;
    }

    public function buildPrompt(): self
    {
        $this->prompt = Speedrun::getOverview();
        $this->prompt .= "You are creating a new migration for the {$this->model_name} model.";
        $this->prompt .= " The fields to include are " . $this->createFieldsString() . '.';

        if ($relationships = $this->createRelationshipsString()) {
            $this->prompt .= " All relationships for this task (possibly including some irrelevant ones) are " . $relationships . '.';
            $this->prompt .= " Include relevant belongsTo relationships for the {$this->model_table} table.";
            $this->prompt .= " Do not include extra 'many to many' schemas – only create a single schema for {$this->model_table}.";
        }

        $this->prompt .= " Do not include schemas for any other tables – only create a single schema for {$this->model_name}.";
        $this->prompt .= " Use an anonymous function for the migration.";
        $this->prompt .= " Fields marked * are required. Fields not marked * are nullable. Use regular Laravel timestamps. Respond only with the Laravel migration file, including all fields which should be present.";
        $this->prompt .= " A sample Laravel migration template is included below:\n\n";
        $this->prompt .= " ```php\n";
        $this->prompt .= $this->getSampleMigration();
        $this->prompt .= "\n```";

        return $this;
    }

    protected function runAI(): self
    {
        $this->response = GetAIWithFallback::run($this->prompt);
        return $this;
    }

    protected function filterPHP(): self
    {
        $this->response = FilterPHP::run($this->response);
        return $this;
    }

    public function placeFile(): self
    {
        $date = now()->format('Y_m_d_His');
        $this->file_path = base_path("database/migrations/{$date}_create_{$this->model_table}_table.php");

        $this->success = File::put($this->file_path, $this->response);

        return $this;
    }

    protected function fixSpecificMigrationIssues(): self
    {
        $response = str($this->response);

        // *****
        // Switch `class CreatePostsTable extends Migration`  to `return new class extends Migration`
        $regex = '/class\s+\w+\s+extends\s+Migration/';
        if ($response->match($regex)) {
            $response->replaceMatches($regex, 'return new class extends Migration'); // switch out the syntax.
            $response->replaceLast('}', '};'); // close the last bracket with a semicolon.
        }

        return $this;

    }

    protected function confirmHasResponse(): self
    {
        if (!$this->response) {
            throw new ConfusedLLMException('It looks like the LLM did not understand the request.');
        }
        return $this;
    }

    protected function verifyValidMigrationFile(): self
    {
        $this->response = match (str($this->response)->substrCount('Schema::create')) {
            0 => '', // If there are no schemas, we messed up
            1 => $this->response,  // If there is one, we are good
            default => $this->filterMigration($this->response) // If there are multiple, let's remove the extras.
        };

        return $this;
    }

    protected function filterMigration(string $response): string
    {
        $prompt = "Below is a Laravel migration with multiple schemas declared. Remove all schemas except the first one.\n";
        $prompt .= $response;

        $response = GetAIWithFallback::run($prompt);
        return FilterPHP::run($response);
    }

    protected function createFieldsString(): string
    {
//        $titleCaseModel = str($this->model_name)->title()->toString();
        return collect($this->task['Models'][$this->model_name])
            ->implode(', ');
    }

    protected function createRelationshipsString(): string
    {
        return collect($this->task['Relationships'] ?? [])
            ->implode(', ');
    }

    protected function getSampleMigration(): string
    {
        $path = base_path('vendor/iambateman/speedrun/resources/stubs/migration.php.stub');
        $file = File::get($path);

        return str($file)->replace('TABLE_NAME', $this->model_table);
    }


}