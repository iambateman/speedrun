<?php

namespace Iambateman\Speedrun\Actions\Tasks;

use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;

class AddLogToTask {

    use AsAction;

    public function handle(string $task_path, string $log): void
    {
        $task = GetTask::run($task_path);

        if(!array_key_exists('Logs', $task)) {
            $task['Logs'] = [];
        }

        $task['Logs'][] = $log;

        UpdateTask::run($task_path, $task);

    }

}