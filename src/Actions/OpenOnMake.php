<?php

namespace Iambateman\Speedrun\Actions;

use Iambateman\Speedrun\Actions\RequestAICompletion;
use Iambateman\Speedrun\Exceptions\ConfusedLLMException;
use Iambateman\Speedrun\Helpers\Helpers;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;

class OpenOnMake {

    use AsAction;


    public function handle($path): bool
    {
        if (config('speedrun.openOnMake')) {

            exec(
                config('speedrun.codeEditor') . ' ' .
                escapeshellarg($path)
            );

            return true;
        }

        return false;

    }


}