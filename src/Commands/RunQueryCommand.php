<?php

namespace Iambateman\Speedrun\Commands;

use Iambateman\Speedrun\Actions\RequestAICompletion;
use Iambateman\Speedrun\Exceptions\ConfusedLLMException;
use Iambateman\Speedrun\Helpers\Helpers;
use Iambateman\Speedrun\Speedrun;
use Iambateman\Speedrun\Traits\GetModelRelationshipsTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Nette\Utils\Reflection;

class RunQueryCommand extends Command {

    use GetModelRelationshipsTrait;

    public $signature = 'speedrun:run-query-command {input}';

    public $description = 'Run a query';

    protected string $prompt;

    protected string $response;

    public function handle(): int
    {
        Helpers::dieInProduction();
        
        $this->prompt = "You are a Laravel developer building a valid Eloquent query which does the following: '{$this->argument('input')}'. Respond only with the query to be run. Do not assign to a variable. If you aren't sure which query to run, say 'unsure'.";
        $this->prompt .= "\n";
        $this->prompt .= $this->getModels();

//        dd($this->prompt);
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

    protected function getModels()
    {
        $subprompt = " Below are all the application's Eloquent Models, along with the fields and relationships for each model. Only use these models, relationships, and fields to generate the query.";
        $subprompt .= "\n";
        $models = Helpers::getModels();

        foreach ($models as $model) {

            $subprompt .= " Model: {$model}.";

            if ($columnsString = $this->getModelFields($model)) {
                $subprompt .= " Fields: {$columnsString}.";
            }

            if ($relationships = $this->getRelationships($model)) {
                $subprompt .= " Relationships: {$relationships}\n\n";
            }
        }

        return $subprompt;
    }

    /**
     * @param $model
     * @return string
     *
     * Get the model's relationship methods and return them as a string
     */
    protected function getRelationships($model): string
    {
        $modelReflection = Helpers::invade(app($model));

        return collect($modelReflection->reflected->getMethods())
            ->filter(function (\ReflectionMethod $method) {
                $returnType = $method->getReturnType();
                return in_array($returnType, [
                    'Illuminate\Database\Eloquent\Relations\HasMany',
                    'Illuminate\Database\Eloquent\Relations\HasManyThrough',
                    'Illuminate\Database\Eloquent\Relations\BelongsTo',
                    'Illuminate\Database\Eloquent\Relations\BelongsToMany',
                    'Illuminate\Database\Eloquent\Relations\HasOne',
                    'Illuminate\Database\Eloquent\Relations\HasOneThrough',
                    'Illuminate\Database\Eloquent\Relations\MorphMany',
                    'Illuminate\Database\Eloquent\Relations\MorphOne',
                    'Illuminate\Database\Eloquent\Relations\MorphOneOrMany',
                    'Illuminate\Database\Eloquent\Relations\MorphTo',
                    'Illuminate\Database\Eloquent\Relations\Pivot',
                ]);
            })
            ->map(fn(\ReflectionMethod $method) => $method->name . '()')
            ->implode(', ');

    }

    protected function getModelSchema(string $modelClass): Collection
    {
        $model = $this->getModel($modelClass);
        $table = $this->getModelTable($model);

        return collect($table->getColumns())->map(fn($column) => $column->getName())->keys();
    }

    protected function checkResponseValidity()
    {
        // At first, try GPT-4
        if (str($this->response)->startsWith('unsure')) {
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
