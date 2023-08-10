<?php

namespace Iambateman\Speedrun\Actions\Tasks;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\Yaml\Yaml;

class UpdateTask {

    use AsAction;

    public function handle(string $task_path, string|array $yaml): void
    {
        if(is_array($yaml)) {
            $yaml = Yaml::dump($yaml);
        }

        File::replace($task_path, $yaml);
    }

}