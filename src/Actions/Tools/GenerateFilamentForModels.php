<?php

namespace Iambateman\Speedrun\Actions\Tools;

use Iambateman\Speedrun\Helpers\Helpers;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Lorisleiva\Actions\Concerns\AsAction;
use function Laravel\Prompts\text;

class GenerateFilamentForModels {

    use AsAction;

    public string $commandSignature = 'speedrun:generate-filament-for-models {models?*}';

    public bool $success = false;
    public string $message = '';

    public function handle(?Collection $models): void
    {
        if (!Helpers::command_exists('filament:install')) {
            $this->message = "Filament is not installed.";
            return;
        }

//         Artisan::call('migrate');
//         echo Artisan::output();

        // Try to fall back to all models in the system
        if ($models->isEmpty()) {
            $models = GetModels::run()
                ->map(fn($qualifiedModel) => class_basename($qualifiedModel));
        }

        // There are no models.
        if($models->isEmpty()) {
            $this->message = 'No models found.';
            return;
        }

        foreach ($models as $model) {
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
            $model = text("What model would you like to generate? (or press enter for all models)");
            if ($model) {
                $models = $models->add($model);
            }
        }

        // Make sure we're only sending basename
        $models = $models->map(fn($model) => class_basename($model));

        // Enforce the uppercase model convention
        $models = $models->map(fn($model) => str($model)->title()->toString());

        $this->handle($models);

        if ($this->message) {
            $command->info($this->message);
        }

        ($this->success) ?
            $command->info('Success') : $command->info('Fail');
    }

}