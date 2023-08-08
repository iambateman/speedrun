<?php

namespace Iambateman\Speedrun\Actions\Utilities;

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