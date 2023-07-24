<?php

namespace Iambateman\Speedrun\Commands;

use Illuminate\Console\Command;

class RunHelpCommand extends Command {

    public $signature = 'speedrun:run-help-command {input}';

    public $description = 'Help with the Speedrun commands';

    protected string $prompt;
    protected string $response;

    public function handle(): int
    {

        $this->comment('@TODO write help material.');

        return self::SUCCESS;
    }


}
