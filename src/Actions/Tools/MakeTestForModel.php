<?php

namespace Iambateman\Speedrun\Actions\Tools;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Lorisleiva\Actions\Concerns\AsAction;

class MakeTestForModel {

    use AsAction;

    public string $commandSignature = 'speedrun:make-test-for-model {model_name}';

    public bool $success = false;
    public string $message = '';
    public string $model_name = '';

    public function handle($model_name = ''): void
    {
        Artisan::call("generate:factory $model_name");
        echo Artisan::output();
    }

    public function asCommand(Command $command)
    {
        $this->model_name = str(
            implode(' ', $command->argument('model_name'))
        );

        $this->handle($this->model_name);

        if ($this->message) {
            $command->info($this->message);
        }

        ($this->success) ?
            $command->info('Success') : $command->info('Fail');
    }

}