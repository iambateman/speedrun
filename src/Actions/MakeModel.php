<?php

namespace Iambateman\Speedrun\Actions;

use Iambateman\Speedrun\Helpers\Helpers;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Console\Command;


class MakeModel {

    use AsAction;

    public string $commandSignature = 'speedrun:make-model {model_name?}';

    protected string $path;
    protected string $model_name;
    protected string $prompt;
    protected array $brief;
    protected bool $success = false;
    protected string $message = '';

    public function handle(string $model_name)
    {
        // Test for existence
        $this->path = Helpers::getModelPath($model_name, true);

        if (!$this->path) {
            $this->message = 'Model already exists.';
            return;
        }

        // Get initial data
        $this->model_name = Helpers::handleModelName($model_name, true);
        $this->brief = Speedrun::getBrief();
        $this->buildPrompt();

        // Run the AI request
        $response = GetAIWithFallback::run($this->prompt);

        // Process the response
        $this->placeFile($response);

        CheckForBugs::run($this->path, "In particular, check for duplicated methods, and make sure namespace App\Models; is directly below <?php, with no extra line break.");
    }


    public function buildPrompt(): self
    {
        $this->prompt = Speedrun::getOverview();
        $this->prompt .= "You are creating a new model called {$this->model_name}.";
        $this->prompt .= " All relationships for the entire app are " . $this->createRelationshipsString() . '.';
        $this->prompt .= " Include relevant relationships in this model. Include \$guarded = [].";
        $this->prompt .= " Respond only with the Laravel model file. Do not include additional explanation.";
        $this->prompt .= " Start the file exactly like this: `<?php\nnamespace App\Models;`. Assume all models have factories.";
        $this->prompt .= " A sample Laravel migration template is included below:\n\n";
        $this->prompt .= " ```php\n";
        $this->prompt .= $this->getSampleModel();
        $this->prompt .= "\n```";
        return $this;
    }

    protected function getSampleModel(): string
    {
        $path = base_path('vendor/iambateman/speedrun/resources/stubs/model.php.stub');
        return File::get($path);
    }

    public function placeFile(string $response)
    {
        $data = Speedrun::filterPhp($response);
        info($data);
        $this->success = File::put($this->path, $data);
    }


    public function createRelationshipsString(): string
    {
        return collect($this->brief['Relationships'])
            ->implode(', ');
    }

    public function asCommand(Command $command)
    {
        $model_name = $command->argument('model_name');


        if ($model_name) {
            $models = collect($model_name);
        } else {
            $brief = Speedrun::getBrief();
            $models = collect($brief['Models'])->keys();
        }

        foreach ($models as $model) {
            $command->info("Creating model for $model");
            $this->handle($model);
        }

        if ($this->message) {
            $command->info($this->message);
        }

        $modelsString = $models->implode(', ');

        ($this->success) ?
            $command->info("Successfully created model for $modelsString") :
            $command->warn("Failed making model for $modelsString");
    }


}