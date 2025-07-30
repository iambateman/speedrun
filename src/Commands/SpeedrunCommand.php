<?php

namespace Iambateman\Speedrun\Commands;

use Iambateman\Speedrun\Exceptions\NoAPIKeyException;
use Iambateman\Speedrun\Helpers\Helpers;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Console\Command;
use Illuminate\Support\Stringable;

class SpeedrunCommand extends Command {

    public $signature = 'speedrun {input?*}';

    public $description = 'Get GPT commands from natural language';

    protected Stringable $inputText;

    protected string $prompt;

    protected string $response;

    public function handle(): int
    {

        Helpers::dieInProduction();

        $this->confirmAPIKey();

        $this->stringifyInput();

        match ($this->inputText->words(1, '')->toString()) {
            'make', 'create', 'add' => $this->decideWhichMake(),
            'run', 'start', 'go' => $this->call('speedrun:run-task', ['task_path' => $this->inputText, '--via_speedrun' => true]),
            'undo', 'undo:task' => $this->call('speedrun:undo-task', ['task_path' => $this->inputText, '--via_speedrun' => true]),
            'install', 'require', 'composer' => $this->call('speedrun:install-composer-package', ['input' => $this->inputText]),
            'who', 'what', 'when', 'where', 'how', 'find', 'query' => $this->call('speedrun:run-query-command', ['input' => $this->inputText]),
            'help' => $this->call('speedrun:run-help-command', ['input' => $this->inputText]),
            default => $this->call('speedrun:run-artisan-command', ['input' => $this->inputText])
        };

        $this->comment('All done');

        return self::SUCCESS;
    }

    protected function decideWhichMake(): void
    {
        match ($this->inputText->words(2, '')->toString()) {
            'make task', 'create task', 'add task' => $this->call('speedrun:make-task'),
            'make view', 'create view', 'add view', 'make views', 'create views', 'add views', 'add livewire', 'make livewire', 'create livewire' => $this->call('speedrun:make-views', ['--via_speedrun' => true]),
//            'make model' => $this->call('speedrun:make-model', ['--via_speedrun' => true]),
            'add package' => $this->call('speedrun:install-composer-package', ['input' => $this->inputText]),
            default => $this->info("We weren't sure what to run.")
        };
    }

    protected function stringifyInput()
    {
        $this->inputText = str(
            implode(' ', $this->argument('input'))
        );

        if ($this->inputText == '') {
            $this->inputText = str($this->ask('how can I help you?'));
        }
    }

    protected function confirmAPIKey()
    {
        if (!Speedrun::getKey()) {
            throw new NoAPIKeyException('Please add SPEEDRUN_OPENAI_API_KEY to .env');
        }
    }

}
