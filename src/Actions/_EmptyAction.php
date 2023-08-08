<?php

namespace Iambateman\Speedrun\Actions;

use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;

class _EmptyAction {

    use AsAction;

    public string $commandSignature = 'speedrun:SET_SIGNATURE';

    public bool $success = false;
    public string $message = '';

    public function handle(): void
    {

    }

    public function asCommand(Command $command)
    {
        $this->handle();

        if($this->message) {
            $command->info($this->message);
        }

        ($this->success) ?
            $command->info('Success') : $command->info('Fail');
    }

}