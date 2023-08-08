<?php

namespace Iambateman\Speedrun\Actions\Tools;

use Iambateman\Speedrun\Helpers\Helpers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Lorisleiva\Actions\Concerns\AsAction;
use function Laravel\Prompts\text;

class GenerateFilamentForModels {

    use AsAction;

    public string $commandSignature = 'speedrun:generate-filament-for-models {models?*}';

    public bool $success = false;
    public string $message = '';

    public function handle(?array $models): void
    {
         if (! Helpers::command_exists('filament:install')) {
             $this->message = "Filament is not installed.";
             return;
         }

         Artisan::call('migrate');
         echo Artisan::output();

         if(!$models) {
             $models = GetModels::run()
                 ->map(fn($qualifiedModel) => class_basename($qualifiedModel));
         }

         foreach($models as $model) {
             $this->generateFilamentResource($model);
         }

         $this->success = true;
         $this->message = 'Generated Filament Resources!';
    }

    public function generateFilamentResource(string $model_name)
    {
          Artisan::call("make:filament-resource $model_name --generate");
          echo Artisan::output();
    }

    public function asCommand(Command $command)
    {
        $models = collect($command->argument('models'));

        if ($models->isEmpty()) {
            $models = $models->add(text("What model would you like to generate?"));
        }

        // Make sure we're only sending basename
        $models = $models->map(fn($model) => class_basename($model));

        // Enforce the uppercase model convention
        $models = $models->map(fn($model) => str($model)->title()->toString());

        $this->handle($models->toArray());

        if($this->message) {
            $command->info($this->message);
        }

        ($this->success) ?
            $command->info('Success') : $command->info('Fail');
    }

}