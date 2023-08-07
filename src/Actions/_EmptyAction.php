<?php

namespace Iambateman\Speedrun\Actions;

use Iambateman\Speedrun\Actions\RequestAICompletion;
use Iambateman\Speedrun\Exceptions\ConfusedLLMException;
use Iambateman\Speedrun\Helpers\Helpers;
use Iambateman\Speedrun\Speedrun;
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