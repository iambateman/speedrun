<?php

namespace Iambateman\Speedrun\Actions;

use Iambateman\Speedrun\Actions\RequestAICompletion;
use Iambateman\Speedrun\Exceptions\ConfusedLLMException;
use Iambateman\Speedrun\Helpers\Helpers;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;

class RunTask {

    use AsAction;

    public string $commandSignature = 'speedrun:run-task';

    public bool $success = false;
    public string $message = '';
    public array $task;
    public string $task_path;

    public function handle(): void
    {
//        Speedrun::runPreflightSafetyChecks();

        $this->task = GetTask::run();
        $this->task_path = $this->task["Path"];

        if(!$this->task || $this->task == []) {
            $this->message = 'No incomplete task found.';
        }

        foreach ($this->task['Models'] as $model => $fields) {

            echo "Making migration & model for {$model}\n";
            MakeMigrationToCreateModel::run($model, $this->task_path);

            MakeModel::run($model, $this->task_path);
            echo Artisan::output();
        }

        Artisan::call('speedrun:make-many-to-many-migrations');
        echo Artisan::output();

        Artisan::call('speedrun:make-factory');
        echo Artisan::output();

        $this->success = true;

        File::replaceInFile("Complete: false", "Complete: true", $this->task['Path']);

    }

    public function asCommand(Command $command)
    {
        $this->handle();

        if ($this->message) {
            $command->info($this->message);
        }

        ($this->success) ?
            $command->info('Successfully ran task') : $command->info('Fail');
    }

}