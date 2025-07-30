<?php

namespace Iambateman\Speedrun\Commands;

use Illuminate\Console\Command;

class DescribeCommand extends Command
{
    protected $signature = 'speedrun:describe-feature {feature?}';
    protected $description = 'Describe an existing feature in the codebase';

    public function handle(): int
    {
        $feature = $this->argument('feature');

        if (!$feature) {
            $feature = $this->ask('What feature would you like me to describe from the existing codebase?');
        }

        if (!$feature) {
            $this->error('No feature specified.');
            return Command::FAILURE;
        }

        $this->info("📝 Analyzing feature: {$feature}");
        $this->newLine();
        $this->line("🤖 Claude Code will now analyze the codebase and describe this feature...");
        $this->newLine();
        $this->line("Feature to describe: {$feature}");

        return Command::SUCCESS;
    }
}