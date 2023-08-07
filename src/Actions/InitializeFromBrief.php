<?php

namespace Iambateman\Speedrun\Actions;

use Iambateman\Speedrun\Actions\RequestAICompletion;
use Iambateman\Speedrun\Exceptions\ConfusedLLMException;
use Iambateman\Speedrun\Helpers\Helpers;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Lorisleiva\Actions\Concerns\AsAction;

class InitializeFromBrief {

    use AsAction;

    public string $commandSignature = 'speedrun:initialize-from-brief';

    public bool $success = false;
    public array $brief;

    public function handle(): void
    {
        Speedrun::runPreflightSafetyChecks();

        $this->brief = Speedrun::getBrief();

        foreach ($this->brief['Models'] as $model => $fields) {
            echo "Making migration & model for {$model}";
            Artisan::call('speedrun:make-migration-to-create-model', ['model_name' => $model]);
            echo Artisan::output();

            Artisan::call('speedrun:make-model', ['model_name' => $model]);
            echo Artisan::output();
        }

        Artisan::call('speedrun:make-many-to-many-migrations');
        echo Artisan::output();

        Artisan::call('speedrun:make-factory');
        echo Artisan::output();

        $this->success = true;

    }

    public function asCommand(Command $command)
    {
        $this->handle();

        ($this->success) ?
            $command->info('Successfully initialized from brief') : $command->info('Fail');
    }

}