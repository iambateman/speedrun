<?php

namespace Iambateman\Speedrun\Actions\Tools;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class MakeFactory {

    use AsAction;

    public string $commandSignature = 'speedrun:make-factory {model_name?*}';

    public bool $success = false;
    public string $message = '';
    public string $model_name = '';

    public function handle($model_name = ''): void
    {
        try {
            DB::beginTransaction();


            Artisan::call("generate:factory $model_name");
            $output = Artisan::output();

            if (in_array("Error", str_split($output, 5))) {
                throw new \Exception($output);
            }

            DB::commit();

        } catch (\Exception|\Error $e) {
            DB::rollback();

            CatchExceptions::run($e->getMessage());

        }

        if (str($output)->contains("Model factory created")) {
            $this->success = true;
        }

        echo $output;

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