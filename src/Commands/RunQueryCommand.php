<?php

namespace Iambateman\Speedrun\Commands;

use Illuminate\Console\Command;
use Iambateman\Speedrun\Actions\RequestAICompletion;
use Iambateman\Speedrun\Exceptions\ConfusedLLMException;
use Iambateman\Speedrun\Helpers\Helpers;
use Iambateman\Speedrun\Speedrun;
use Iambateman\Speedrun\Exceptions\ProductionException;
use App\Console\Kernel;

class RunQueryCommand extends Command {

    public $signature = 'speedrun:run-query-command {input}';

    public $description = 'Run a query';

    protected string $prompt;
    protected string $response;

    public function handle(): int
    {
        Helpers::dieInProduction();

        $this->prompt = "You are helping a Laravel developer build an eloquent query from natural language. Respond only with the query which should be run. If you aren't sure which query to run, say 'unsure'.";
        $this->prompt .= $this->getModels();
        $this->prompt .= " The developer requested a query which does the following: {$this->argument('input')}";

        $this->response = RequestAICompletion::from($this->prompt);

        $this->checkResponseValidity();

        if (Speedrun::doubleConfirm()) {
            if ($this->confirm("run {$this->response}?")) {
                $this->runRequestedCommand();
            };
        } else {
            $this->runRequestedCommand();
        }

        return self::SUCCESS;
    }

    protected function getModels()
    {
        $models = Helpers::getModels();
        return " Available models to query are {$models->implode(', ')}.";
    }


    protected function checkResponseValidity()
    {
        // At first, try GPT-4
        if (str($this->response)->startsWith('unsure')) {
            $this->reRunWithBetterModel();
            info('went with GPT-4');

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
        $response = str($this->response)->remove(';'); // don't allow a ; at the end.

        $command = <<<EOT
php artisan tinker --execute="dump($response)"
EOT;

        exec($command, $output, $returnVar);

        foreach ($output as $row) {
            $this->comment($row);
        }
    }

}
