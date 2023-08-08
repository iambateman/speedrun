<?php

namespace Iambateman\Speedrun\Actions\Tools;

use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;

class CatchExceptions {

    use AsAction;

    public string $commandSignature = 'speedrun:catch-exceptions {exceptions}';

    public bool $success = false;
    public string $message = '';
    public string $variable;

    public function handle($exceptions)
    {
        dd('hi');
    }

    public function asCommand(Command $command)
    {
        $variable = $command->argument('exceptions');

        $this->handle($variable);

        if ($this->message) {
            $command->info($this->message);
        }

        ($this->success) ?
            $command->info('Success') : $command->info('Fail');
    }

}