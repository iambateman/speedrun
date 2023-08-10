<?php

namespace Iambateman\Speedrun\Actions\Tasks;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;

class UndoTask {

    use AsAction;

    public string $commandSignature = 'speedrun:undo-task {task_path?}';

    public bool $success = false;
    public string $message = '';
    public ?string $task_path;
    public ?array $task;

    public function handle(?string $task_path = '')
    {
        $this->task = GetTask::run($task_path);
        $this->task_path = $task_path ?? $this->task['Path'] ?? '';

        if (!$this->task || !$this->task_path) {
            $this->message = "Error – Task not found. It's likely that all tasks are marked Complete.\n\nTry entering the path of the task.";
            return;
        }

        if (!array_key_exists('Logs', $this->task)) {
            $this->message = "Nothing to undo.";
            return;
        }


        foreach ($this->task['Logs'] as $log) {
            $log = str($log);
            if ($log->startsWith('Created')) {
                $path = $log->remove('Created ');
                $deleted = File::delete($path);

                if ($deleted && ($key = array_search($log->toString(), $this->task['Logs'])) !== false) {
                    unset($this->task['Logs'][$key]);
                }
            }
        }

        // reindex to keep bullets.
        $this->task['Logs'] = array_values($this->task['Logs']);

        // If it was complete, it no longer is...
        $this->task['Complete'] = false;

        UpdateTask::run($this->task_path, $this->task);

        $this->success = true;
        $this->message = "Removed created files.";

    }

    public function asCommand(Command $command)
    {
        $task_path = $command->argument('task_path');

        // *****
        // Sometimes tasks are invoked as commands in speedrun
        // when that happens, we get the 'undo' text at the beginning.
        $task_path = str($task_path);
        if($task_path->startsWith('undo')) {
            $task_path = '/' . $task_path->after('/');
        }


        $this->handle($task_path);

        if ($this->message) {
            $command->info($this->message);
        }

        ($this->success) ?
            $command->info('Success') : $command->info('Fail');
    }

}