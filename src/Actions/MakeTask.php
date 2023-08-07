<?php

namespace Iambateman\Speedrun\Actions;

use Iambateman\Speedrun\Actions\RequestAICompletion;
use Iambateman\Speedrun\Exceptions\ConfusedLLMException;
use Iambateman\Speedrun\Helpers\Helpers;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class MakeTask {

    use AsAction;

    public string $commandSignature = 'speedrun:make-task';

    protected string $description;
    protected string $file_path;
    public bool $success = false;

    public function handle(string $description = ''): void
    {
        $this->description = $description;

        // If there's no description, return a basic stub.
        $response = ($description) ?
            $this->buildTaskWithAI() :
            $this->buildTaskFromStub();

        $this->getFilePath();

        $this->placeFile($response);
    }

    public function asCommand(Command $command)
    {
        $description = text(label: 'What is the task? (Or press enter for empty task)', required: false);

        $this->handle($description);

        ($this->success) ?
            $command->info('Success') : $command->info('Fail');

        $this->openFileAfterCreation();
    }

    public function openFileAfterCreation(): void
    {
        if (config('speedrun.openOnMake') && confirm("Open file?", true)) {
            OpenOnMake::run($this->file_path);
        }
    }

    public function buildTaskFromStub(): string
    {
        $path = base_path('vendor/iambateman/speedrun/resources/stubs/task.php.stub');
        return File::get($path);
    }

    public function buildTaskWithAI(): string
    {
        $prompt = $this->buildPrompt();
        $response = GetAIWithFallback::run($prompt);

        return Speedrun::filterYaml($response);

    }

    protected function getFilePath(): self
    {

        $file_name = now()->format('Y_m_d_His');

        // Only use word count if it's not super long.
        if ($this->description) {
            $task_summary = GetAIWithFallback::run("Return only a 2-6 word overall description summary of {$this->description}");

            $task_summary = str($task_summary);
            if ($task_summary->wordCount() < 8) {
                $file_name .= "_" . $task_summary->slug();
            }
        }

        $this->file_path = base_path("_ai/tasks/{$file_name}.yaml");

        return $this;
    }

    protected function buildPrompt()
    {
        $prompt = <<<END

You are creating a yaml file to describe the structured data of a Laravel app. A sample structure is like this:
```yaml
Complete: false

Task: >
  {$this->description}

Models:
  MODEL_NAME:
      - MODEL_PROPERTY (nullable)

Relationships:
  - MODEL_1 BelongsTo MODEL_2
  - MODEL_3 BelongsToMany MODEL_4

```
END;

        $prompt .= "The description of the task is this: {$this->description} ";
        $prompt .= "Please complete the yaml file for this task. Guess what models, fields, and relationships will be needed based on the description.";
        $prompt .= "Make sure the response is surrounded by a ```yaml code block.";

        return $prompt;
    }

    public function placeFile(string $response)
    {
        $this->success = File::put($this->file_path, $response);
    }

}