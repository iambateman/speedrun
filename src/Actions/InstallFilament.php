<?php

namespace Iambateman\Speedrun\Actions;

use Iambateman\Speedrun\Actions\RequestAICompletion;
use Iambateman\Speedrun\Exceptions\ConfusedLLMException;
use Iambateman\Speedrun\Speedrun;
use Iambateman\Speedrun\Helpers\Helpers;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;

class InstallFilament {

    use AsAction;

    public string $commandSignature = 'speedrun:install-filament';

    public bool $success = false;
    public string $message = '';

    public function handle(): void
    {
        if (Helpers::command_exists('filament:install')) {
            $this->success = true;
            $this->message = "Filament already installed.";
        }
    }

    public function asCommand(Command $command)
    {
        $this->handle();

        if ($this->message) {
            $command->info($this->message);
        }

        ($this->success) ?
            $command->info('Success') : $command->info('Fail');
    }

}