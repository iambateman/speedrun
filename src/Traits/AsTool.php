<?php

namespace Iambateman\Speedrun\Traits;

trait AsTool {

    // Model (not always used)
    public string $model_name;
    public string $model_table;

    // Task
    public array $task;
    public string $task_path;

    // AI prompt and response
    public string $prompt;
    public string $response = '';
    public string $file_path;

    // Send the results back to the command
    public bool $success = false;
    public string $message = '';

}