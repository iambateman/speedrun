<?php

namespace Iambateman\Speedrun\Commands;

use App\Console\Kernel;
use Iambateman\Speedrun\Actions\RequestAICompletion;
use Iambateman\Speedrun\Exceptions\ConfusedLLMException;
use Iambateman\Speedrun\Helpers\Helpers;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunArtisanCommand extends Command
{
    public $signature = 'speedrun:run-artisan-command {input}';

    public $description = 'Run an artisan command';

    protected string $prompt = '';

    protected string $response;

    public function handle(): int
    {
        Helpers::dieInProduction();

        $this->prompt .= "You are helping a Laravel developer write a valid console command from natural language. Respond only with the command which should be run, beginning with php artisan. If you aren't sure which command to run, say 'unsure'.";
        $this->prompt .= $this->getApplicationCommands();
        $this->prompt .= " The developer requested a command which does the following: {$this->argument('input')}";

        $this->response = RequestAICompletion::from($this->prompt);

        $this->checkResponseValidity();

        if (Speedrun::doubleConfirm()) {
            if ($this->confirm("run {$this->response}?")) {
                $this->runRequestedCommand();
            }
        } else {
            $this->runRequestedCommand();
        }

        return self::SUCCESS;
    }

    public function getApplicationCommands(): ?string
    {
        if (! config('speedrun.includeAppCommands')) {
            return false;
        }

        $commands = app()->make(Kernel::class)->all();

        $app_command_names = collect($commands)
            ->keys()
            ->filter(fn ($command) => str($command)->startsWith('app:'))
            ->implode(', ');

        if ($app_command_names) {
            return " the custom artisan commands which can be called for this app are {$app_command_names}. Respond with one of these if you think it is relevant.";
        }

        return false;

    }

    protected function checkResponseValidity()
    {
        if (str($this->response)->startsWith('unsure') || ! str($this->response)->startsWith('php artisan')) {
            $this->reRunWithBetterModel();
            $this->info("Trying query with GPT-4");

            // Then just fail.
            if (str($this->response)->startsWith('unsure')) {
                throw new ConfusedLLMException("Sorry, the AI model wasn't sure how to process that request.");
            }
        }
    }

    protected function reRunWithBetterModel()
    {
        $this->response = RequestAICompletion::from($this->prompt, 'gpt-4');
    }

    protected function runRequestedCommand()
    {
        $shortened_response = str($this->response)->remove('php artisan');
        Artisan::call($shortened_response);
        $output = Artisan::output();
        $this->comment($output);

    }
}
