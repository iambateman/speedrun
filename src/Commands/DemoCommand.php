<?php

namespace Iambateman\Speedrun\Commands;

use Illuminate\Console\Command;

class DemoCommand extends Command {

    public $signature = 'speedrun:demo';

    public $description = 'Demo of Speedrun';

    protected string $prompt;

    protected string $response;

    public function handle(): int
    {

        $this->info('<><>                           <><>');
        usleep(250000);
        $this->info('<><>   Thanks for trying out   <><>');
        usleep(250000);
        $this->info('<><>          |                <><>');
        usleep(250000);
        $this->info('<><>          SPEED            <><>');
        usleep(250000);
        $this->info('<><>              RUN          <><>');
        usleep(250000);
        $this->info('<><>                |          <><>');
        usleep(250000);
        $this->info('<><>        the easiest        <><>');
        usleep(250000);
        $this->info('<><>           way to          <><>');
        usleep(250000);
        $this->info('<><>           write           <><>');
        usleep(250000);
        $this->info('<><>          commands         <><>');

       $first = $this->choice("What should we tackle first?", ['Artisan Commands', 'Database Queries', 'Composer Packages', 'Robot\'s choice']);

        return self::SUCCESS;
    }

}
