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

        if (
            // We specifically want `make` to work for making models and other things. so we allow make task
            in_array($this->inputText, ['make task', 'create task', 'add task'])
        ) {
            $this->call('speedrun:make-task');

        } else {
            match ($this->inputText->words(1, '')->toString()) {
                'run', 'start', 'go' => $this->call('speedrun:run-task'),
                'install', 'require', 'composer' => $this->call('speedrun:install-composer-package', ['input' => $this->inputText]),
                'who', 'what', 'when', 'where', 'how', 'find', 'query' => $this->call('speedrun:run-query-command', ['input' => $this->inputText]),
                'help' => $this->call('speedrun:run-help-command', ['input' => $this->inputText]),
                default => $this->call('speedrun:run-artisan-command', ['input' => $this->inputText])
            };
        }

        $this->comment('All done');

        return self::SUCCESS;
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
            throw new NoAPIKeyException('Please add OPENAI_API_KEY to .env');
        }
    }

}
