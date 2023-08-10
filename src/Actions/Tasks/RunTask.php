<?php

namespace Iambateman\Speedrun\Actions\Tasks;

use Iambateman\Speedrun\Actions\Tools\MakeManyToManyMigrations;
use Iambateman\Speedrun\Actions\Tools\MakeMigrationToCreateModel;
use Iambateman\Speedrun\Actions\Tools\MakeModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;
use function Laravel\Prompts\confirm;

class RunTask {

    use AsAction;

    public string $commandSignature = 'speedrun:run-task {task_path?}';

    public bool $success = false;
    public string $message = '';
    public ?array $task;
    public string $task_path;

    public function handle(?array $task): void
    {
//        PreflightSafetyChecks::run();

        $this->task = $task ?? GetTask::run();
        $this->task_path = $this->task["Path"];

        if (!$this->task || $this->task == []) {
            $this->message = 'No incomplete task found.';
        }

        foreach ($this->task['Models'] as $model => $fields) {

            echo "Making migration & model for {$model}\n";
            MakeMigrationToCreateModel::run($model, $this->task_path);

            MakeModel::run($model, $this->task_path);
            echo Artisan::output();
        }

        MakeManyToManyMigrations::run($this->task_path);

        Artisan::call('speedrun:make-factory');
        echo Artisan::output();

        $this->success = true;

        File::replaceInFile("Complete: false", "Complete: true", $this->task['Path']);

    }

    public function asCommand(Command $command)
    {

        $task_path = $command->argument('task_path');

        if($task_path == 'run' || $task_path == 'run task') {
            $task_path = '';
        }

        // *****
        // Sometimes tasks are invoked as commands in speedrun
        // when that happens, we remove the 'run' text at the beginning.
        if($task_path &&
            str($task_path)->startsWith('run') &&
            str($task_path)->contains('/')) {
            $task_path = '/' . str($task_path)->after('/');
        }

        $this->task = GetTask::run($task_path);

        $short_description = str($this->task['Task'])->words(10);
        if (confirm("[RUN THIS?] {$short_description}")) {
            $this->handle($this->task);
        }

        if ($this->message) {
            $command->info($this->message);
        }

        ($this->success) ?
            $command->info('Successfully ran task') : $command->info('Fail');
    }

}