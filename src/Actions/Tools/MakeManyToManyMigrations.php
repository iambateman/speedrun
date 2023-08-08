<?php

namespace Iambateman\Speedrun\Actions\Tools;

use Iambateman\Speedrun\Actions\Utilities\FilterPHP;
use Iambateman\Speedrun\Actions\Utilities\GetAIWithFallback;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;


class MakeManyToManyMigrations {

    use AsAction;

    public string $commandSignature = 'speedrun:make-many-to-many-migrations';

    public Collection $manyToManyList;
    public string $prompt;
    public array $brief;
    public bool $success = false;
    public string $message = '';

    public function handle()
    {
        // Get initial data
        $this->brief = Speedrun::getBrief();
        $this->buildPromptToDetect();

        // Run the AI request
        $response = GetAIWithFallback::run($this->prompt);

        if ($response == 'none') {
            $this->message = "No many to many migrations found.";
            $this->success = true;
            return false;
        }


        // Process the response
        $this->placeMigrationsInArray($response);

        foreach ($this->manyToManyList as $migration) {
            $this->processEachMigration($migration);
        }
    }

    public function asCommand(Command $command)
    {
        $command->info("Making Many to Many Migrations");

        $this->handle();

        if ($this->message) {
            $command->info($this->message);
        }

        ($this->success) ?
            $command->info('Successfully created Many to Many migrations') :
            $command->warn('Failed creating Many to Many migrations');
    }

    /**
     * @param $migration // comes in as 'Contact - Tag'
     */
    public function processEachMigration(string $migration)
    {
        $first_model = str($migration)->before('-')->singular()->slug('_');
        $second_model = str($migration)->after('-')->singular()->slug('_');

        $migrationFile = str($this->getSampleMigration())
            ->replace('FIRST', $first_model)
            ->replace('SECOND', $second_model);

        $this->placeFile($first_model . '_' . $second_model, $migrationFile);
    }

    protected function getSampleMigration(): string
    {
        $path = base_path('vendor/iambateman/speedrun/resources/stubs/many_to_many_migration.php.stub');
        return File::get($path);
    }

    public function placeFile(string $models, $data)
    {
        $date = now()->format('Y_m_d_His');
        $path = base_path("database/migrations/{$date}_create_{$models}_table.php");
        $data = FilterPHP::run($data);

        $this->success = File::put($path, $data);
    }

    public function buildPromptToDetect()
    {
        $this->prompt = Speedrun::getOverview();
        $this->prompt .= " All relationships for the entire app are " . $this->createRelationshipsString() . '.';
        $this->prompt .= " Are there any 'Many to Many' relationships in this application? If so, which models? List each model pair on it's own line as `model - model` in alphabetical order.";
        $this->prompt .= " Do not include duplicates";
        $this->prompt .= " If there are no 'Many to Many' relationships, respond with 'none'";
    }

    public function placeMigrationsInArray(string $response): void
    {
        $this->manyToManyList = str($response)->remove("\"\"\"")->explode("\n");

    }

    public function createRelationshipsString(): string
    {
        return collect($this->brief['Relationships'])
            ->implode(', ');
    }


}