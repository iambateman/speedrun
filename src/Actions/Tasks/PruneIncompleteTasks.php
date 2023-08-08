<?php

namespace Iambateman\Speedrun\Actions\Tasks;

use Iambateman\Speedrun\Speedrun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;
use function Laravel\Prompts\confirm;

class PruneIncompleteTasks {

    use AsAction;

    public string $commandSignature = 'speedrun:prune-incomplete-tasks';

    public bool $success = false;
    public string $message = '';

    public function handle(): void
    {
        $tasks = (new GetTask())->getTasks()->where('Complete', '!=', true);

        foreach ($tasks as $task) {
            File::delete($task['Path']);
        }

        $this->success = true;
    }

    public function asCommand(Command $command)
    {
        if (confirm("Permanently delete tasks?")) {
            $this->handle();
        }

        if ($this->message) {
            $command->info($this->message);
        }

        ($this->success) ?
            $command->info('Success') : $command->info('Fail');
    }

}