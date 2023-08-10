<?php

namespace Iambateman\Speedrun\Actions\Tools;

use Iambateman\Speedrun\Actions\Tasks\AddLogToTask;
use Iambateman\Speedrun\Actions\Tasks\GetTask;
use Iambateman\Speedrun\Actions\Utilities\FilterPHP;
use Iambateman\Speedrun\Actions\Utilities\GetAIWithFallback;
use Iambateman\Speedrun\Helpers\Helpers;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;


class MakeModel {

    use AsAction;

    public string $commandSignature = 'speedrun:make-model {model_name?}';

    protected string $path;
    protected string $model_name;
    protected string $prompt;
    protected array $task;
    protected bool $success = false;
    protected string $message = '';

    public function handle(string $model_name, string $task_path)
    {
        // Test for existence
        $this->path = Helpers::getModelPath($model_name, true);

        if (!$this->path) {
            $this->message = 'Model already exists.';
            return;
        }

        // Get initial data
        $this->model_name = Helpers::handleModelName($model_name, true);
        $this->task = GetTask::run($task_path);
        $this->buildPrompt();

        // Run the AI request
        $response = GetAIWithFallback::run($this->prompt);

        // Process the response
        $this->placeFile($response);

        CheckForBugs::run($this->path, "In particular, check for duplicated methods.");

        AddLogToTask::run(
            task_path: $task_path,
            log: "Created {$this->path}"
        );
    }


    public function buildPrompt(): self
    {
        $this->prompt = Speedrun::getOverview();
        $this->prompt .= "You are creating a new model called {$this->model_name}.";

        if ($relationships = $this->createRelationshipsString()) {
            $this->prompt .= " All relationships for the entire app are " . $relationships . '.';
            $this->prompt .= " Include relevant relationships in this model. ";
        }

        $this->prompt .= " Include \$guarded = [].";
        $this->prompt .= " Respond only with the Laravel model file. Do not include additional explanation.";
        $this->prompt .= " Start the file exactly like this: `<?php\nnamespace App\Models;`. Assume all models have factories.";
        $this->prompt .= " A sample Laravel model template is included below:\n\n";
        $this->prompt .= " ```php\n";
        $this->prompt .= $this->getSampleModel();
        $this->prompt .= "\n```";
        return $this;
    }

    protected function getSampleModel(): string
    {
        $path = base_path('vendor/iambateman/speedrun/resources/stubs/model.php.stub');
        return File::get($path);
    }

    public function placeFile(string $response)
    {
        $data = FilterPHP::run($response);
        info($data);
        $this->success = File::put($this->path, $data);
    }


    public function createRelationshipsString(): string
    {
        return collect($this->task['Relationships'] ?? [])
            ->implode(', ');
    }

    public function asCommand(Command $command)
    {
        $model_name = $command->argument('model_name');
        $task_path = $command->argument('task_path');


        if ($model_name) {
            $models = collect($model_name);
        } else {
            $task = GetTask::run($task_path);
            $this->handle($model_name, $task_path);
            $models = collect($task['Models'])->keys();
        }

        foreach ($models as $model) {
            $command->info("Creating model for $model");
            $this->handle($model_name, $task_path);
        }

        if ($this->message) {
            $command->info($this->message);
        }

        $modelsString = $models->implode(', ');

        ($this->success) ?
            $command->info("Successfully created model for $modelsString") :
            $command->warn("Failed making model for $modelsString");
    }


}