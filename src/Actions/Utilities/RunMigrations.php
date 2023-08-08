<?php

namespace Iambateman\Speedrun\Actions\Utilities;

use Error;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RunMigrations {

    use AsAction;

    public string $commandSignature = 'speedrun:run-migrations';

    public bool $success = false;

    public function handle(): void
    {
        try {
            DB::beginTransaction();


            Artisan::call('migrate');
            $artisanOutput = Artisan::output();

            if (in_array("Error", str_split($artisanOutput, 5))) {
                throw new Exception($artisanOutput);
            }

            DB::commit();

            info("successfully ran migrations");
            info($artisanOutput);
            $this->success = true;

        } catch (Exception|Error $e) {
            DB::rollback();
            dd($e->getMessage());
        }
    }

    public function asCommand(Command $command)
    {
        $command->info('running all migrations');
        $this->handle();

        ($this->success) ?
            $command->info('Successfully ran migrations') :
            $command->warn('Failed running migrations.');
    }

}