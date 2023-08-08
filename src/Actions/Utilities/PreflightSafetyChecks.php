<?php

namespace Iambateman\Speedrun\Actions\Utilities;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Lorisleiva\Actions\Concerns\AsAction;

class PreflightSafetyChecks {

    use AsAction;

    public function handle(): void
    {
//        echo 'Running migrations';
//        Artisan::call('speedrun:run-migrations');
//        echo Artisan::output();

        echo 'Running tests';
        Artisan::call('speedrun:run-tests');
        echo Artisan::output();
    }

}