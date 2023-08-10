<?php

namespace Iambateman\Speedrun\Actions\Tasks;

use Iambateman\Speedrun\Actions\Utilities\FilterYAML;
use Iambateman\Speedrun\Actions\Utilities\GetAIWithFallback;
use Iambateman\Speedrun\Actions\Utilities\OpenOnMake;
use Iambateman\Speedrun\Helpers\ToolList;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\Yaml\Yaml;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class MakeTask {

    use AsAction;

    public string $commandSignature = 'speedrun:make-task';

    protected string $description;
    protected string $file_path;
    protected Collection $clarifications;
    public bool $success = false;

    public function handle(?string $description): void
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

        if ($description) {
            $this->askClarifyingQuestions($description, $command);
        }

        $this->handle($description);

        ($this->success) ?
            $command->info('Success') : $command->info('Fail');

        $this->openFileAfterCreation();
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

        $response = FilterYAML::run($response);

        // Append clarifications.
        if ($this->clarifications && $this->clarifications->isNotEmpty()) {
            $response .= "\n\nClarifications:\n";
            $response .= $this->clarifications->map(function ($clarification) use ($response) {
                if ($clarification['answer']) {
                    return "  - 'Q: {$clarification['question']}. A: {$clarification['answer']}'\n";
                }
                return null;
            })->implode('');
        }

        return $response;

    }



    protected function askClarifyingQuestions(string $description, Command $command): self
    {

        $prompt = "You are helping a Laravel developer work on a task for an application.\n";
        $prompt .= " You will read the description and not carry it out, only seek to clarify the details. Specifically, you will summarise a list of super short bullets of areas that need clarification to help you successfully define and accomplish the task.";
        $prompt .= " Respond only with the list of bullets. Make sure they are bullets, not numbers. If everything is clear, respond with 'All is clear'.\n\n";
        $prompt .= "TASK DESCRIPTION: {$description}";

        $response = GetAIWithFallback::run($prompt);

        if (str($response)->contains('All is clear')) {
            return $this;
        }

        // *****
        // Prepare the Clarifications
        $this->clarifications = str($response)
            ->remove('- ')
            ->remove('"""')
            ->explode(PHP_EOL)
            ->take(7)
            ->map(fn($question) => ['question' => $question]);

        // *****
        // Show each one beforehand.
        $command->info("The following questions were asked by the AI to better understand your task:");
        $this->clarifications->each(function ($clarification) use ($command) {
            $command->info("- {$clarification['question']}");
        });

        // *****
        // Get answers
        $this->clarifications = $this->clarifications->map(function (array $clarification) {
            $clarification['answer'] = text($clarification['question'] . ' (Or press enter)');
            return $clarification;
        });

        return $this;
    }

    protected function getFilePath(): self
    {

        $file_name = now()->format('Y_m_d_His');

        // Only use word count if it's not super long.
        if ($this->description && str($this->description)->wordCount() < 6) {
            $file_name .= "_" . str($this->description)->slug();
        } elseif ($this->description) {
            $task_summary = GetAIWithFallback::run("Return only a 2-6 word description of '{$this->description}'. Use exact words where possible.");

            $task_summary = str($task_summary);
            info($task_summary);
            if ($task_summary->wordCount() < 8) {
                $file_name .= "_" . $task_summary->slug();
            }
        }

        $this->file_path = base_path("_ai/tasks/{$file_name}.yml");

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

# Optional: only include if a new model is being created.
Models:
  MODEL_NAME:
      - "MODEL_PROPERTY (nullable)"
      
# Optional: only include if a new model is being created.
Relationships:
  - "MODEL_1 BelongsTo MODEL_2"
  - "MODEL_3 BelongsToMany MODEL_4"

# Optional: only include if there are tools which will help accomplish this task.
Subtasks:
  - "php artisan speedrun:example-command-with-no-parameters"
  - "php artisan speedrun:example-command-with-parameters parameter"
 

```
END;

        $prompt .= "\n\nTASK DESCRIPTION: {$this->description}.\n";
        $prompt .= " Please complete the yaml file for this task.";
        $prompt .= " First, decide if the user is requesting to create a new model. If so, guess what models, fields, and relationships will be needed based on the description. If no models or relationships are needed, remove those from the yaml file. Many tasks do not involve creating models.";
        $prompt .= " \n\n";

        $prompt .= "Subtasks use one or more of the tools below. If a tool should be used, put it's command and any parameters in the 'Subtasks' field.";

        $tools = json_encode(ToolList::get());
        $prompt .= "```json\n{$tools}```\n";

        if ($this->clarifications && $this->clarifications->isNotEmpty()) {
            $prompt .= "\nCLARIFICATIONS:";
            $prompt .= "\nYou asked the following clarifying questions about this task, and these were the answers:\n";

            foreach ($this->clarifications as $clarification) {
                if ($clarification['answer']) {
                    $prompt .= "- {$clarification['question']} {$clarification['answer']}\n";
                }
            }
        }

        $prompt .= " LAST NOTES:\n";
        $prompt .= " - Make sure the response is surrounded by a ```yaml code block.\n";
        $prompt .= " - Remove comment blocks.\n";
        $prompt .= " - Make sure models are title cased.";

        info($prompt);

        return $prompt;
    }

    public function placeFile(string $response)
    {
        // append Path
         $response .= "\n\nPath: {$this->file_path}";

        $this->success = File::put($this->file_path, $response);
    }

    public function openFileAfterCreation(): void
    {
        if (config('speedrun.openOnMake') && confirm("Open file?", true)) {
            OpenOnMake::run($this->file_path);
        }
    }

}