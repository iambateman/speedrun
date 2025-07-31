<?php

namespace Iambateman\Speedrun\Commands;

use Iambateman\Speedrun\Speedrun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupCommand extends Command
{
    protected $signature = 'speedrun:feature:clean {slug}';
    protected $description = 'Clean up feature folder.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        /**
         * The slug for the feature
         */
        $slug = $this->argument('slug');

        /**
         * Get the path to the feature folder
         */
        $path = Speedrun::directory("wip/$slug");

        /**
         * Make sure the folder exists.
         */
        if(! File::exists($path)) {
            $this->info("The folder \"{$path}\" does not exist. Something is wrong.");
            return Command::FAILURE;
        }

        /**
         * Delete folder.
         */
        File::delete($path);

        return Command::SUCCESS;
    }
}
