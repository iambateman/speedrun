<?php

namespace Iambateman\Speedrun\Actions\Tools;

use Iambateman\Speedrun\Actions\Tasks\GetTask;
use Iambateman\Speedrun\Actions\Utilities\FilterHTML;
use Iambateman\Speedrun\Actions\Utilities\FilterPHP;
use Iambateman\Speedrun\Actions\Utilities\GetAIWithFallback;
use Iambateman\Speedrun\DTO\BladeComponent;
use Iambateman\Speedrun\Exceptions\ConfusedLLMException;
use Iambateman\Speedrun\Speedrun;
use Iambateman\Speedrun\Traits\AsTool;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;

class MakeLivewirePage {

    use AsAction;
    use AsTool;

    public string $commandSignature = 'speedrun:make-livewire-page {task_path?}';

    public function handle(?string $task_path): Collection
    {

        $this
//            ->setInitialData($task_path)
            ->buildPrompt()
            ->runAI()
            ->filterHTML()
            ->confirmHasResponse()
            ->fixSpecificIssues()
            ->placeFile();
    }

    public function asCommand(Command $command)
    {
        $task_path = $command->argument('task_path');

        $this->handle($task_path);

        if ($this->message) {
            $command->info($this->message);
        }

        ($this->success) ?
            $command->info("Successfully created Livewire") :
            $command->warn("Failed making Livewire");
    }

    protected function setInitialData(string $task_path): self
    {
        $this->task_path = $task_path;
        $this->task = GetTask::run($task_path);

        return $this;
    }

    public function buildPrompt(): self
    {
        $components = $this->getBladeComponentsString();

        // *****
        // user: "Create a rounds page."

        $this->prompt = "You are creating using Laravel, Livewire, AlpineJS, and Tailwind to create a page called rounds.blade.php.";
        $this->prompt .= " The page will live at /rounds. It's subpages are /rounds/create and /rounds/[id]. The page has a page title of 'Rounds'.";
        $this->prompt .= " On the page, create a table using included blade components for all the rounds in the system. If there are no rounds, show a 'create your first round' button.";
        $this->prompt .= "\n\nBLADE COMPONENTS:";
        $this->prompt .= "\nThe following components are available to use in the app. Prefer these to regular HTML, where possible.\n";
        $this->prompt .= $components;
        $this->prompt .= "\n\nPAGE CREATION NOTES: (1) Use Livewire pagination. (2) use \<x-container\> as the outside wrapper of the component";
        $this->prompt .= " (3) Use a mixture of the blade components above, custom HTML, Tailwind classes, to compose the page.";
        $this->prompt .= " (4) use TailwindCSS classes for style, including responsive breakpoint classes.";
        $this->prompt .= " (5) Respond only with the HTML, wrapped in an ```html code block.";

        return $this;
    }

    protected function runAI(): self
    {
        $this->response = GetAIWithFallback::run($this->prompt, 'gpt-3.5-turbo-16k');
        return $this;
    }

    protected function filterHTML(): self
    {
        $this->response = FilterHTML::run($this->response);

        return $this;
    }

    protected function confirmHasResponse(): self
    {
        if (!$this->response) {
            throw new ConfusedLLMException('It looks like the LLM did not understand the request.');
        }
        return $this;
    }

    protected function fixSpecificIssues(): self
    {

        dd($this->response);
        // None right now.

        return $this;
    }

    public function placeFile(): self
    {
//        $date = now()->format('Y_m_d_His');
//        $this->file_path = resource_path("database/migrations/{$date}_create_{$this->model_table}_table.php");
//        $this->success = File::put($this->file_path, $this->response);

        return $this;
    }


    protected function getBladeComponentsString(): string
    {
        $components = collect(GetBladeComponents::run());

        return $components->map(function (BladeComponent $component) {
            return $component->getElement();
        })->implode('\n');

    }

}